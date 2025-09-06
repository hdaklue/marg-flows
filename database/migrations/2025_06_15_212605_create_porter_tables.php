<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('porter.database_connection') ?: config('database.default');
        $idStrategy = config('porter.id_strategy', 'ulid');

        Schema::connection($connection)
            ->create(config('porter.table_names.roster'), static function (Blueprint $table) use ($idStrategy) {
                $table->id();

                // Model type columns (optimized for MySQL key length limits)
                $table->string('assignable_type', 100); // Realistic class name length
                $table->string('roleable_type', 100);   // Realistic class name length

                // ID columns - type depends on configured strategy
                match ($idStrategy) {
                    'integer' => [
                        $table->unsignedBigInteger('assignable_id'), // For auto-increment IDs
                        $table->unsignedBigInteger('roleable_id'),   // For auto-increment IDs
                    ],
                    'uuid' => [
                        $table->uuid('assignable_id'),               // For UUID IDs
                        $table->uuid('roleable_id'),                 // For UUID IDs
                    ],
                    default => [  // 'ulid' or any other strategy
                        $table->ulid('assignable_id'),             // For ULID/string IDs
                        $table->ulid('roleable_id'),               // For ULID/string IDs
                    ]
                };

                $table->string('role_key', 64); // Encrypted/hashed role key (64 chars for SHA-256)
                $table->timestamps();

                // Composite unique constraint - optimized column order for MySQL
                $table->unique(
                    ['assignable_type', 'assignable_id', 'roleable_type', 'roleable_id', 'role_key'],
                    'porter_unique'
                );

                // Performance indexes
                $table->index(['assignable_id', 'assignable_type'], 'porter_assignable_idx');
                $table->index(['roleable_id', 'roleable_type'], 'porter_roleable_idx');
                $table->index(['role_key'], 'porter_role_key_idx');

                // Composite indexes for common queries
                $table->index(['assignable_type', 'assignable_id', 'roleable_type'], 'porter_user_entity_idx');
                $table->index(['roleable_type', 'roleable_id'], 'porter_entity_idx');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('porter.database_connection') ?: config('database.default');

        Schema::connection($connection)->dropIfExists(config('porter.table_names.roster', 'roster'));
    }
};
