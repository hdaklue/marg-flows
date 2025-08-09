<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverable_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('deliverable_id');
            $table->integer('version_number');
            $table->string('status')->default('draft'); // draft, submitted, revision_needed
            $table->text('notes')->nullable();
            $table->json('files')->nullable(); // File attachments for this version
            $table->ulid('created_by');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('deliverable_id')->references('id')->on('deliverables')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->unique(['deliverable_id', 'version_number']);
            $table->index(['deliverable_id', 'status']);
            $table->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverable_versions');
    }
};