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
        Schema::connection('business_db')->create('design_feedbacks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Base feedback fields
            $table->string('creator_id');
            $table->index('creator_id');

            $table->string('status')->default(FeedbackStatus::OPEN->value);
            $table->string('urgency')->default(FeedbackUrgency::NORMAL->value);

            // Content
            $table->text('content');

            // Polymorphic feedbackable relationship
            $table->ulidMorphs('feedbackable');

            // Status and resolution
            $table->text('resolution')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Design-specific fields
            $table->decimal('x_coordinate', 12, 8)->comment('X coordinate on the design/image');
            $table->decimal('y_coordinate', 12, 8)->comment('Y coordinate on the design/image');
            $table->decimal('width', 12, 8);
            $table->decimal('height', 12, 8);

            // $table->enum('annotation_type', [
            //     'point', 'rectangle', 'circle', 'arrow', 'text',
            //     'polygon', 'area', 'line', 'freehand',
            // ])->default('point')->comment('Type of annotation');
            $table->json('annotation_data')->nullable()->comment('Additional annotation metadata (shape, color, size, etc.)');
            $table->json('area_bounds')->nullable()->comment('Bounds for area-based annotations (x, y, width, height)');
            $table->string('color', 50)->nullable()->comment('Annotation color/theme');
            $table->decimal('zoom_level', 5, 3)->nullable()->comment('Zoom level when annotation was created');

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

            // Design-specific indexes
            $table->index(['x_coordinate', 'y_coordinate']);
            $table->index('color');
            $table->index('zoom_level');

            // Coordinate-based queries
            $table->index(['feedbackable_type', 'feedbackable_id', 'x_coordinate', 'y_coordinate'], 'dgf_feedbackable_coords');

            // Color-based grouping
            $table->index(['feedbackable_type', 'feedbackable_id', 'color'], 'dgf_feedbackable_color');
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('design_feedbacks');
    }
};
