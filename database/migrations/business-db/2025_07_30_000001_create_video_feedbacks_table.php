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
        Schema::connection('business_db')->create('video_feedbacks', function (Blueprint $table) {
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

            // Video-specific fields
            $table->enum('feedback_type', ['frame', 'region'])->default('frame');
            $table->decimal('timestamp', 10, 3)->nullable()->comment('Frame timestamp in seconds (for frame feedback)');
            $table->decimal('start_time', 10, 3)->nullable()->comment('Start time in seconds (for region feedback)');
            $table->decimal('end_time', 10, 3)->nullable()->comment('End time in seconds (for region feedback)');
            $table->integer('x_coordinate')->nullable()->comment('X coordinate on video frame');
            $table->integer('y_coordinate')->nullable()->comment('Y coordinate on video frame');
            $table->json('region_data')->nullable()->comment('Additional region metadata');

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

            // Video-specific indexes
            $table->index('feedback_type');
            $table->index(['feedback_type', 'status']);

            // Frame feedback indexes
            $table->index('timestamp');
            $table->index(['feedback_type', 'timestamp']);
            $table->index(['x_coordinate', 'y_coordinate']);

            // Region feedback indexes
            $table->index(['start_time', 'end_time']);
            $table->index(['feedback_type', 'start_time', 'end_time']);

            // Time range queries
            $table->index(['feedbackable_type', 'feedbackable_id', 'start_time'], 'vf_feedbackable_start_time');
            $table->index(['feedbackable_type', 'feedbackable_id', 'timestamp'], 'vf_feedbackable_timestamp');

            // Coordinate-based queries
            $table->index(['feedbackable_type', 'feedbackable_id', 'x_coordinate', 'y_coordinate'], 'vf_feedbackable_coords');
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('video_feedbacks');
    }
};
