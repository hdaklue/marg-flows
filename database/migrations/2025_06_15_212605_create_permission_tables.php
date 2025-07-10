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
        // $teams = config('permission.teams');
        // $tableNames = config('permission.table_names');
        // $columnNames = config('permission.column_names');
        // $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        // $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        // throw_if(empty($tableNames), new Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.'));
        // throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), new Exception('Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.'));

        // Schema::create('permissions', static function (Blueprint $table) {
        //     // $table->engine('InnoDB');
        //     $table->ulid('id')->primary(); // permission id
        //     $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)// For MyISAM use string('guard_name', 25);
        //     $table->timestamps();

        //     $table->unique(['name']);
        // });

        Schema::create('roles', static function (Blueprint $table) {
            // $table->engine('InnoDB');
            $table->ulid('id')->primary(); // role id

            $table->foreignUlid('tenant_id')->references('id')->on('tenants');

            $table->string('name');

            $table->timestamps();
            $table->index('tenant_id', 'roles_team_foreign_key_index');
            $table->index(['tenant_id', 'name', 'id']);
            $table->unique(['tenant_id', 'name']);
        });

        // Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
        //     $table->unsignedBigInteger($pivotPermission);

        //     $table->string('model_type');
        //     $table->foreignUlid('model_id');
        //     $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

        //     $table->foreign($pivotPermission)
        //         ->references('id') // permission id
        //         ->on($tableNames['permissions'])
        //         ->onDelete('cascade');
        //     if ($teams) {
        //         $table->foreignUlid($columnNames['team_foreign_key']);
        //         $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

        //         $table->primary([$columnNames['team_foreign_key'], $pivotPermission, 'model_id', 'model_type'],
        //             'model_has_permissions_permission_model_type_primary');

        //     } else {
        //         $table->primary([$pivotPermission, 'model_id', 'model_type'],
        //             'model_has_permissions_permission_model_type_primary');
        //     }

        // });

        Schema::create(config('role.table_names.model_has_roles'), static function (Blueprint $table) {
            // $table->unsignedBigInteger($pivotRole);
            $table->id();
            $table->string('model_type');
            $table->foreignUlid('model_id');

            $table->ulidMorphs('roleable');
            $table->foreignUlid('role_id')
                ->references('id') // role id
                ->on('roles')
                ->onDelete('cascade');

            // $table->primary([
            //     'role_id',
            //     'model_id',
            //     'model_type',
            //     'roleable_id',
            //     'roleable_type',
            // ], 'model_has_roles_composite_primary');
            $table->unique(
                ['model_type', 'model_id', 'roleable_type', 'roleable_id', 'role_id'],
                'model_has_roles_unique',
            );

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

        });

        // Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
        //     $table->unsignedBigInteger($pivotPermission);
        //     $table->unsignedBigInteger($pivotRole);

        //     $table->foreign($pivotPermission)
        //         ->references('id') // permission id
        //         ->on($tableNames['permissions'])
        //         ->onDelete('cascade');

        //     $table->foreign($pivotRole)
        //         ->references('id') // role id
        //         ->on($tableNames['roles'])
        //         ->onDelete('cascade');

        //     $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        // });

        // app('cache')
        //     ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
        //     ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // $tableNames = config('permission.table_names');

        // throw_if(empty($tableNames), new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.'));

        Schema::drop('roles');
        Schema::drop('model_has_roles');
        // Schema::drop($tableNames['model_has_permissions']);
        // Schema::drop($tableNames['roles']);
        // Schema::drop($tableNames['permissions']);
    }
};
