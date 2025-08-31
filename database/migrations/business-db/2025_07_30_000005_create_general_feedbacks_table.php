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
        Schema::connection('business_db')->create('general_feedbacks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Base feedback fields
            $table->string('creator_id');

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

            // General feedback fields
            $table->json('metadata')->nullable()
                ->comment('Flexible metadata storage for various feedback types');
            $table->string('feedback_category')->nullable()->comment('Optional category for organization');
            $table->json('custom_data')->nullable()->comment('Additional custom data as needed');

            $table->timestamps();

            // Base feedback indexes
            $table->index('creator_id');
            $table->index('status');
            $table->index('urgency');
            $table->index(['urgency', 'status']);
            $table->index('resolved_at');
            $table->index('resolved_by');
            $table->index(['creator_id', 'created_at']);
            $table->index(['feedbackable_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['creator_id', 'status']);

            // General feedback indexes
            $table->index('feedback_category');
            $table->index(['feedback_category', 'status']);

            // Category-based queries
            $table->index(['feedbackable_type', 'feedbackable_id', 'feedback_category'], 'gf_feedbackable_category');

            // Category distribution analysis
            $table->index(['feedback_category', 'urgency', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('general_feedbacks');
    }
};
