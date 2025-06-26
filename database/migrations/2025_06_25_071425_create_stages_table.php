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
        Schema::create('stages', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->ulidMorphs('stageable');
            $table->string('color');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['name']);
            $table->unique(['name', 'stageable_type', 'stageable_id']);

            $table->index(['stageable_type', 'stageable_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
