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
        $rbaconnection = config('margrbac.database.connection');
        Schema::connection($rbaconnection)->create('profiles', function (Blueprint $table) {

            $table->ulid('id')->primary();
            $table->string('user_id'); // References user ID from RBAC database
            $table->string('avatar')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $rbaconnection = config('margrbac.database.connection');
        Schema::connection($rbaconnection)->dropIfExists('profiles');
    }
};
