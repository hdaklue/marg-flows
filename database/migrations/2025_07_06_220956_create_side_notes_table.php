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
        Schema::create('side_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->text('content');
            $table->ulidMorphs('sidenoteable');
            $table->string('owner_id');
            $table->timestamps();

            // Indexes for performance (owner_id already indexed by foreign key)
            $table->index(['owner_id', 'sidenoteable_type', 'sidenoteable_id']);
            $table->index('owner_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('side_notes');
    }
};
