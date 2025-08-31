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
        Schema::connection(config('margrbac.database.connection'))
            ->create('login_logs', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->references('id')->on('users');
                $table->string('ip_address');
                $table->string('user_agent');
                $table->timestamps();

                $table->index('user_id');
                $table->index('ip_address');
                $table->index('user_agent');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('margrbac.database.connection'))
            ->dropIfExists('login_logs');
    }
};
