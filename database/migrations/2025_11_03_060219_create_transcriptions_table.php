<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transcriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('input_text')->nullable();
            $table->longText('output_text')->nullable();
            $table->string('audio_file_path')->nullable();
            $table->string('model_used')->default('default');
            $table->string('language', 10)->default('en');
            $table->enum('type', ['text_to_speech', 'speech_to_text']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->float('processing_time')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('char_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'type', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcriptions');
    }
};
