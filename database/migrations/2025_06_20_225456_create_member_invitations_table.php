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
        Schema::create('member_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sender_id')->references('id')->on('users');
            $table->string('email');
            $table->json('role_data');
            $table->date('expires_at');
            $table->date('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'email']);
            $table->index('accepted_at', 'email');
            $table->index('expires_at');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_invitations');
    }
};
