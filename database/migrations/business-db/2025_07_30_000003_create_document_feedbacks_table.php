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
        Schema::connection('business_db')->create('document_feedbacks', function (Blueprint $table) {
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

            // Document-specific fields
            $table->string('block_id')->comment('Editor.js block identifier');
            $table->string('element_type')->nullable()->comment('Type of block element (paragraph, header, list, etc.)');
            $table->json('position_data')->nullable()->comment('Position metadata (selection, offset, etc.)');
            $table->string('block_version')->nullable()->comment('Version/hash of the block content when feedback was created');
            $table->json('selection_data')->nullable()->comment('Text selection data (start, end, selected text)');

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

            // Document-specific indexes
            $table->index('block_id');
            $table->index(['block_id', 'status']);
            $table->index('element_type');
            $table->index(['element_type', 'status']);
            $table->index('block_version');

            // Document and block combination queries
            $table->index(['feedbackable_type', 'feedbackable_id', 'block_id'], 'df_feedbackable_block');
            $table->index(['feedbackable_type', 'feedbackable_id', 'element_type'], 'df_feedbackable_element');

            // Text selection queries
            $table->index(['block_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('document_feedbacks');
    }
};
