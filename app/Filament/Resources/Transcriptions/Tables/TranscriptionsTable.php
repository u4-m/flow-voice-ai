<?php

namespace App\Filament\Resources\Transcriptions\Tables;

use App\Models\Transcription;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'speech_to_text' => 'Speech to Text',
                        'text_to_speech' => 'Text to Speech',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'speech_to_text' => 'info',
                        'text_to_speech' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('language')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color('gray'),
                    
                TextColumn::make('model_used')
                    ->label('Model')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        str_replace(['openai/', 'facebook/'], '', $state)
                    ),
                    
                IconColumn::make('status')
                    ->icon(fn (string $state): string => match ($state) {
                        'completed' => 'heroicon-o-check-circle',
                        'processing' => 'heroicon-o-arrow-path',
                        'failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                    
                TextColumn::make('processing_time')
                    ->label('Time')
                    ->suffix('s')
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state, 2) : '-'),
                    
                TextColumn::make('created_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'speech_to_text' => 'Speech to Text',
                        'text_to_speech' => 'Text to Speech',
                    ]),
                    
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
                    
                SelectFilter::make('language')
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
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Transcription $record): string => 
                        $record->type === 'speech_to_text' 
                            ? route('transcriptions.download-text', $record)
                            : route('transcriptions.download-audio', $record)
                    )
                    ->visible(fn (Transcription $record): bool => $record->status === 'completed' && ($record->output_text || $record->audio_file_path)),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
