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
        Schema::create('recents', function (Blueprint $table) {
            $table->id();
            $table->ulid('user_id');
            $table->ulidMorphs('recentable');
            $table->ulid('tenant_id');
            $table->timestamp('interacted_at');

            $table->unique([
                'user_id',
                'recentable_type',
                'recentable_id',
                'tenant_id',
            ], 'recency_unique');
            $table->index('interacted_at');
            $table->index(['user_id', 'recentable_type', 'tenant_id']);
            $table->index(['user_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recents');
    }
};
