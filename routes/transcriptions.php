<?php

use App\Http\Controllers\TranscriptionController;
use Illuminate\Support\Facades\Route;

// Web routes (for downloads)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/transcriptions/{transcription}/download-text', [TranscriptionController::class, 'downloadText'])
        ->name('transcriptions.download-text');
        
    Route::get('/transcriptions/{transcription}/download-audio', [TranscriptionController::class, 'downloadAudio'])
        ->name('transcriptions.download-audio');
});

// API routes (for processing)
Route::middleware(['api', 'auth:sanctum'])->prefix('api')->group(function () {
    Route::post('/transcriptions/{transcription}/process', [TranscriptionController::class, 'process'])
        ->name('api.transcriptions.process');
});
