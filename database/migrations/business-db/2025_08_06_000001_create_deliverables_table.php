<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('business_db')->create('deliverables', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('format'); // design, video, audio, document
            $table->string('type'); // video_cover, square, story, etc.
            $table->string('status'); // draft, in_progress, review, revision_requested, completed
            $table->integer('priority')->default(3); // 1-5 scale
            $table->integer('order_column')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('success_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Store config specs in database for independence
            $table->json('format_specifications');
            $table->json('settings')->nullable();
            
            $table->ulid('flow_id');
            $table->ulid('stage_id')->nullable();
            $table->ulid('creator_id');
            $table->ulid('tenant_id');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys reference tables in main database, stored as ULIDs only
            
            $table->index(['flow_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['success_date']);
            $table->index(['format', 'type']);
        });
    }

    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('deliverables');
    }
};