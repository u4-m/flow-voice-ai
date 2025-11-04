<?php

namespace App\Filament\Resources\Transcriptions;

use App\Filament\Resources\Transcriptions\Pages\CreateTranscriptions;
use App\Filament\Resources\Transcriptions\Pages\EditTranscriptions;
use App\Filament\Resources\Transcriptions\Pages\ListTranscriptions;
use App\Filament\Resources\Transcriptions\Schemas\TranscriptionsForm;
use App\Filament\Resources\Transcriptions\Tables\TranscriptionsTable;
use App\Models\Transcription;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

class TranscriptionsResource extends Resource
{
    protected static ?string $model = Transcription::class;
    protected static ?string $modelLabel = 'Transcription';
    protected static ?string $recordTitleAttribute = 'title';
    
    public static function getNavigationLabel(): string
    {
        return 'Transcriptions';
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'AI Services';
    }
    
    public static function getNavigationSort(): ?int
    {
        return 1;
    }
    
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return TranscriptionsForm::configure($schema);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return TranscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranscriptions::route('/'),
            'create' => CreateTranscriptions::route('/create'),
            'edit' => EditTranscriptions::route('/{record}/edit'),
        ];
    }
}
