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
            ->table('users', function (Blueprint $table) {
                $table->foreignUlid('active_tenant_id')
                    ->nullable()
                    ->references('id')->on('tenants');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('margrbac.database.connection'))
            ->table('users', function (Blueprint $table) {
                $table->dropForeign(['active_tenant_id']);
                $table->dropColumn('active_tenant_id');
            });
    }
};
