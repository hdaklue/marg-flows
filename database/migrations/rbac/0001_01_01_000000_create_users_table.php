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
            ->create('users', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('account_type')->default('user');
                $table->string('password');

                $table->string('timezone')->nullable();
                $table->string('username', 15)->unique()->nullable();

                $table->rememberToken();
                $table->timestamps();

                $table->index('email');
                $table->index('account_type');
                $table->index('username');

            });

        Schema::connection(config('margrbac.database.connection'))
            ->create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });

        Schema::connection(config('margrbac.database.connection'))
            ->create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('margrbac.database.connection'))->dropIfExists('users');
        Schema::connection(config('margrbac.database.connection'))->dropIfExists('password_reset_tokens');
        Schema::connection(config('margrbac.database.connection'))->dropIfExists('sessions');
    }
};
