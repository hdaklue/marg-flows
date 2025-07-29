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
        Schema::connection('business_db')->create('feedbacks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Creator
            $table->string('creator_id');
            $table->index('creator_id');

            $table->string('status')->default(FeedbackStatus::OPEN);

            // Content
            $table->text('content');

            // Metadata - stores FeedbackMetadata as JSON
            $table->json('metadata');

            // Polymorphic feedbackable - now in same database!
            $table->ulidMorphs('feedbackable');

            // Status and resolution
            $table->text('resolution')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('urgency')->default(FeedbackUrgency::NORMAL);

            $table->timestamps();

            // Optimized indexes for common queries
            $table->index('status');
            $table->index('urgency');
            $table->index(['urgency', 'status']);
            $table->index('resolved_at');
            $table->index('resolved_by');
            $table->index(['creator_id', 'created_at']);
            $table->index(['feedbackable_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['creator_id', 'status']);

            // Note: Can add foreign key constraints for specific morph types if needed
            // For now, relying on application-level integrity via services

            // Note: JSON indexes can be added via raw SQL post-migration if needed
            // MySQL 8.0+ supports multi-valued indexes: INDEX ((CAST(metadata->'$.type' AS CHAR(50))))
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('feedbacks');
    }
};
