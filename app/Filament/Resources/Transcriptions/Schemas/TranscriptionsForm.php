<?php

namespace App\Filament\Resources\Transcriptions\Schemas;

use App\Models\Transcription;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TranscriptionsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Basic Information Group
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, Set $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    }),

                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Radio::make('type')
                    ->options([
                        'speech_to_text' => 'Speech to Text',
                        'text_to_speech' => 'Text to Speech',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('model_used', null))
                    ->columnSpanFull(),

                // Configuration Group
                Select::make('language')
                    ->options([
                        'en' => 'English',
                        'es' => 'Spanish',
                        'fr' => 'French',
                        'de' => 'German',
                        'it' => 'Italian',
                        'pt' => 'Portuguese',
                        'ru' => 'Russian',
                        'zh' => 'Chinese',
                        'ja' => 'Japanese',
                        'ko' => 'Korean',
                        'hi' => 'Hindi',
                        'ar' => 'Arabic',
                    ])
                    ->default('en')
                    ->required()
                    ->live(),

                Select::make('model_used')
                    ->label('AI Model')
                    ->options(function (Get $get) {
                        return [
                            'speech_to_text' => [
                                'openai/whisper-base' => 'Whisper Base',
                                'openai/whisper-small' => 'Whisper Small (Recommended)',
                                'openai/whisper-medium' => 'Whisper Medium',
                                'openai/whisper-large' => 'Whisper Large',
                                'facebook/mms-1b-all' => 'Facebook MMS 1B',
                                'facebook/mms-1b-lt' => 'Facebook MMS 1B (Low-resource)',
                            ],
                            'text_to_speech' => [
                                'tts-1' => 'TTS-1 (Standard)',
                                'tts-1-hd' => 'TTS-1 HD (High Quality)',
                            ],
                        ][$get('type')] ?? [];
                    })
                    ->required()
                    ->searchable(),

                Toggle::make('auto_process')
                    ->label('Process automatically')
                    ->default(true)
                    ->helperText('Process the transcription automatically when saved'),

                // Input Group
                FileUpload::make('audio_file_path')
                    ->label('Audio File')
                    ->acceptedFileTypes(['audio/*', 'video/*', '.m4a', '.mp3', '.webm', '.mp4', '.mpga', '.wav', '.m4a', '.mpeg'])
                    ->directory(fn () => 'transcriptions/audio/' . (Auth::id() ?? 'default'))
                    ->visibility('private')
                    ->maxSize(10240) // 10MB
                    ->downloadable()
                    ->previewable()
                    ->visible(fn (Get $get) => $get('type') === 'speech_to_text')
                    ->helperText('Maximum file size: 10MB. Supported formats: MP3, WAV, M4A, MP4, etc.'),

                Textarea::make('input_text')
                    ->label('Text to Convert to Speech')
                    ->maxLength(5000)
                    ->rows(8)
                    ->required(fn (Get $get) => $get('type') === 'text_to_speech')
                    ->visible(fn (Get $get) => $get('type') === 'text_to_speech')
                    ->helperText('Enter the text you want to convert to speech')
                    ->columnSpanFull(),

                // Output Group
                Textarea::make('output_text')
                    ->label('Transcription Result')
                    ->maxLength(10000)
                    ->rows(8)
                    ->readOnly()
                    ->visible(fn (Get $get) => $get('type') === 'speech_to_text')
                    ->helperText('The transcribed text will appear here after processing')
                    ->columnSpanFull(),

                FileUpload::make('output_audio_path')
                    ->label('Generated Audio')
                    ->acceptedFileTypes(['audio/*'])
                    ->directory(fn () => 'transcriptions/output/' . (Auth::id() ?? 'default'))
                    ->visibility('private')
                    ->downloadable()
                    ->previewable()
                    ->visible(fn (Get $get) => $get('type') === 'text_to_speech')
                    ->helperText('The generated audio will appear here after processing'),
            ]);
    }
}