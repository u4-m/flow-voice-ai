<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Transcription extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'input_text',
        'output_text',
        'audio_file_path',
        'model_used',
        'language',
        'status',
        'processing_time',
        'word_count',
        'char_count',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processing_time' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
