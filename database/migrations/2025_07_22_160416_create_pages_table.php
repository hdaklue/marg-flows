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
        Schema::create('pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->json('blocks');

            $table->foreignUlid('creator_id')->references('id')->on('users');

            $table->ulidMorphs('pageable');
            $table->timestamps();

            $table->index('creator_id');
            $table->index('created_at');
            $table->index(['pageable_type', 'created_at']);
            $table->index(['creator_id', 'pageable_type', 'pageable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
