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
            ->create('roles', static function (Blueprint $table) {
                // $table->engine('InnoDB');
                $table->ulid('id')->primary(); // role id

                $table->foreignUlid('tenant_id')->references('id')->on('tenants');

                $table->string('name');

                $table->timestamps();
                $table->index('tenant_id', 'roles_team_foreign_key_index');
                $table->index(['tenant_id', 'name', 'id']);
                $table->unique(['tenant_id', 'name']);
            });

        Schema::connection(config('margrbac.database.connection'))
            ->create('model_has_roles', static function (Blueprint $table) {
                // $table->unsignedBigInteger($pivotRole);
                $table->id();
                $table->string('model_type');
                $table->foreignUlid('model_id');

                $table->ulidMorphs('roleable');
                $table->foreignUlid('role_id')
                    ->references('id') // role id
                    ->on('roles')
                    ->onDelete('cascade');
                $table->unique(
                    ['model_type', 'model_id', 'roleable_type', 'roleable_id', 'role_id'],
                    'model_has_roles_unique',
                );

                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::connection(config('margrbac.database.connection'))
            ->drop('roles');
        Schema::connection(config('margrbac.database.connection'))
            ->drop('model_has_roles');

    }
};
