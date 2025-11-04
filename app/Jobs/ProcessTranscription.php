<?php

namespace App\Jobs;

use App\Models\Transcription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessTranscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutes
    public $backoff = [30, 60, 120]; // Retry after 30s, 1m, 2m

    protected $transcription;

    public function __construct(Transcription $transcription)
    {
        $this->transcription = $transcription->withoutRelations();
    }

    public function handle()
    {
        $transcription = $this->transcription;
        
        try {
            // Update status to processing
            $transcription->update([
                'status' => 'processing',
                'processing_started_at' => now(),
            ]);

            $result = null;
            
            if ($transcription->type === 'speech_to_text') {
                $result = $this->processSpeechToText($transcription);
            } else {
                $result = $this->processTextToSpeech($transcription);
            }

            // Update with results
            $transcription->update([
                'status' => 'completed',
                'processing_time' => now()->diffInSeconds($transcription->processing_started_at),
                'output_text' => $result['output_text'] ?? null,
                'audio_file_path' => $result['audio_file_path'] ?? null,
                'word_count' => $result['word_count'] ?? 0,
                'char_count' => $result['char_count'] ?? 0,
                'metadata' => $result['metadata'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            $transcription->update([
                'status' => 'failed',
                'metadata' => array_merge($transcription->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);
            
            Log::error('Transcription processing failed', [
                'transcription_id' => $transcription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // This will trigger a retry if attempts remain
        }
    }

    protected function processSpeechToText(Transcription $transcription): array
    {
        // Get the audio file path
        $audioPath = Storage::path($transcription->audio_file_path);
        
        // Here you would integrate with your preferred STT service
        // This is a placeholder for the actual implementation
        
        // Example using a hypothetical STT service
        $response = Http::withToken(config('services.stt.api_key'))
            ->timeout(300) // 5 minutes
            ->attach('file', fopen($audioPath, 'r'))
            ->post('https://api.stt-service.com/v1/transcribe', [
                'model' => $transcription->model_used,
                'language' => $transcription->language,
            ]);

        if (!$response->successful()) {
            throw new \Exception('STT API request failed: ' . $response->body());
        }

        $result = $response->json();
        
        return [
            'output_text' => $result['text'] ?? '',
            'word_count' => str_word_count($result['text'] ?? ''),
            'char_count' => mb_strlen($result['text'] ?? ''),
            'metadata' => [
                'model' => $transcription->model_used,
                'language' => $transcription->language,
                'api_response' => $result,
            ],
        ];
    }

    protected function processTextToSpeech(Transcription $transcription): array
    {
        // Here you would integrate with your preferred TTS service
        // This is a placeholder for the actual implementation
        
        // Example using a hypothetical TTS service
        $response = Http::withToken(config('services.tts.api_key'))
            ->timeout(300) // 5 minutes
            ->post('https://api.tts-service.com/v1/synthesize', [
                'text' => $transcription->input_text,
                'model' => $transcription->model_used,
                'language' => $transcription->language,
                'voice' => $this->getVoiceForLanguage($transcription->language),
            ]);

        if (!$response->successful()) {
            throw new \Exception('TTS API request failed: ' . $response->body());
        }

        // Save the audio file
        $audioData = $response->body();
        $fileName = 'tts-' . $transcription->id . '-' . now()->format('YmdHis') . '.mp3';
        $filePath = 'transcriptions/output/' . $fileName;
        
        Storage::put($filePath, $audioData);
        
        return [
            'audio_file_path' => $filePath,
            'word_count' => str_word_count($transcription->input_text),
            'char_count' => mb_strlen($transcription->input_text),
            'metadata' => [
                'model' => $transcription->model_used,
                'language' => $transcription->language,
                'voice' => $this->getVoiceForLanguage($transcription->language),
            ],
        ];
    }
    
    protected function getVoiceForLanguage(string $language): string
    {
        // Map languages to appropriate voices
        $voices = [
            'en' => 'en-US-Wavenet-D',
            'es' => 'es-ES-Standard-A',
            'fr' => 'fr-FR-Standard-A',
            'de' => 'de-DE-Standard-A',
            'it' => 'it-IT-Standard-A',
            'pt' => 'pt-PT-Standard-A',
            'ru' => 'ru-RU-Standard-A',
            'zh' => 'cmn-CN-Standard-A',
            'ja' => 'ja-JP-Standard-A',
            'ko' => 'ko-KR-Standard-A',
            'hi' => 'hi-IN-Standard-A',
            'ar' => 'ar-XA-Standard-A',
        ];
        
        return $voices[$language] ?? $voices['en'];
    }
    
    public function failed(\Throwable $exception)
    {
        // Update the transcription status to failed if all retries are exhausted
        if ($this->transcription) {
            $this->transcription->update([
                'status' => 'failed',
                'metadata' => array_merge($this->transcription->metadata ?? [], [
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]),
            ]);
        }
        
        Log::error('Transcription job failed after all retries', [
            'transcription_id' => $this->transcription->id ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
