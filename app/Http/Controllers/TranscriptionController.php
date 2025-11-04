<?php

namespace App\Http\Controllers;

use App\Models\Transcription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TranscriptionController extends Controller
{
    public function process(Transcription $transcription)
    {
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

            return response()->json([
                'success' => true,
                'data' => $transcription->fresh()
            ]);
            
        } catch (\Exception $e) {
            $transcription->update([
                'status' => 'failed',
                'metadata' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage()
            ], 500);
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
        $fileName = 'tts-' . Str::uuid() . '.mp3';
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
    
    public function downloadText(Transcription $transcription)
    {
        if ($transcription->type !== 'speech_to_text' || !$transcription->output_text) {
            abort(404);
        }
        
        $filename = Str::slug($transcription->title) . '.txt';
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response()->streamDownload(function () use ($transcription) {
            echo $transcription->output_text;
        }, $filename, $headers);
    }
    
    public function downloadAudio(Transcription $transcription)
    {
        if ($transcription->type !== 'text_to_speech' || !$transcription->audio_file_path) {
            abort(404);
        }
        
        if (!Storage::exists($transcription->audio_file_path)) {
            abort(404);
        }
        
        return Storage::download(
            $transcription->audio_file_path,
            Str::slug($transcription->title) . '.mp3',
            ['Content-Type' => 'audio/mpeg']
        );
    }
}
