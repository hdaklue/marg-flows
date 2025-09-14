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
        Schema::connection(config('margrbac.database.connection'))
            ->create('member_invitations', function (Blueprint $table) {
                $table->ulid('id')->primary();

                $table->foreignUlid('sender_id')->references('id')->on('users');
                $table->foreignUlid('receiver_id')->references('id')->on('users');

                $table->json('role_data');

                $table->timestamps();

                $table->index(['sender_id', 'receiver_id']);

            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('margrbac.database.connection'))
            ->dropIfExists('member_invitations');
    }
};
