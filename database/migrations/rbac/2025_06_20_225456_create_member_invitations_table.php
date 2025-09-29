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
                $table->string('receiver_email');
                $table->foreignUlid('tenant_id')->references('id')->on('tenants');

                $table->string('role_key');

                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('expires_at');

                $table->timestamps();

                $table->index('receiver_email');
                $table->index('expires_at');
                $table->index(['sender_id', 'receiver_email']);
                $table->index(['tenant_id', 'receiver_email']);
                $table->index(['tenant_id', 'receiver_email', 'sender_id']);

                $table->unique(['receiver_email', 'tenant_id']);

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
