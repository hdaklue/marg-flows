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
        Schema::connection('business_db')->create('pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->json('blocks');

            // Cross-database reference to users table
            $table->string('creator_id');

            // Polymorphic relation (likely to flows in main db)
            $table->string('pageable_type');
            $table->string('pageable_id');
            $table->timestamps();

            // Optimized indexes for cross-database queries
            $table->index('creator_id');
            $table->index('created_at');
            $table->index(['pageable_type', 'pageable_id']);
            $table->index(['pageable_type', 'created_at']);
            $table->index(['creator_id', 'pageable_type', 'pageable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('business_db')->dropIfExists('pages');
    }
};