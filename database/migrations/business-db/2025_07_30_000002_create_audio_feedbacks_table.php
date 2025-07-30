<?php

declare(strict_types=1);

use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('business_db')->create('audio_feedbacks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Base feedback fields
            $table->string('creator_id');
            $table->index('creator_id');

            $table->string('status')->default(FeedbackStatus::OPEN);
            $table->string('urgency')->default(FeedbackUrgency::NORMAL);

            // Content
            $table->text('content');

            // Polymorphic feedbackable relationship
            $table->ulidMorphs('feedbackable');

            // Status and resolution
            $table->text('resolution')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Audio-specific fields
            $table->decimal('start_time', 10, 3)->comment('Start time in seconds');
            $table->decimal('end_time', 10, 3)->comment('End time in seconds');
            $table->json('waveform_data')->nullable()->comment('Waveform visualization data');
            $table->decimal('peak_amplitude', 5, 4)->nullable()->comment('Peak amplitude in the selection (0.0-1.0)');
            $table->json('frequency_data')->nullable()->comment('Frequency analysis data');

            $table->timestamps();

            // Base feedback indexes
            $table->index('status');
            $table->index('urgency');
            $table->index(['urgency', 'status']);
            $table->index('resolved_at');
            $table->index('resolved_by');
            $table->index(['creator_id', 'created_at']);
            $table->index(['feedbackable_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['creator_id', 'status']);

            // Audio-specific indexes
            $table->index(['start_time', 'end_time']);
            $table->index('start_time');
            $table->index('end_time');
            
            // Duration-based queries (computed on-the-fly)
            $table->index(['feedbackable_type', 'feedbackable_id', 'start_time']);
            
            // Amplitude-based queries
            $table->index('peak_amplitude');
            $table->index(['peak_amplitude', 'status']);
            
            // Overlapping audio regions
            $table->index(['feedbackable_type', 'feedbackable_id', 'start_time', 'end_time']);
            
            // Add constraint to ensure end_time > start_time
            $table->rawIndex('start_time < end_time', 'audio_feedbacks_time_order_check');
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('audio_feedbacks');
    }
};