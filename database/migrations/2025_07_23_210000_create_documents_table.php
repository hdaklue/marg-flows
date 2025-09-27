<?php

declare(strict_types=1);

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
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->json('blocks');

            // Cross-database reference to users' table
            $table->string('creator_id');

            // Cross-database reference to the tenant table for efficient querying
            $table->string('tenant_id');

            // Polymorphic relation (likely to flow in the main db)
            $table->string('documentable_type');
            $table->string('documentable_id');

            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            // Optimized indexes for cross-database queries
            $table->index('creator_id');
            $table->index('tenant_id');
            $table->index('created_at');
            $table->index(['documentable_type', 'documentable_id']);
            $table->index(['documentable_type', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['creator_id', 'documentable_type', 'documentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
