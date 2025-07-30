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
        Schema::create('acknowledgements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('acknowledgeable');
            // $table->foreignUlid('actor_id')->references('id')->on('user');
            $table->string('actor_id');
            $table->timestamps();

            $table->index('actor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acknowledgements');
    }
};
