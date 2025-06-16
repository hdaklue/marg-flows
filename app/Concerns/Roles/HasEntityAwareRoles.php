<?php

namespace App\Concerns\Roles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

/**
 * Entity-Aware Roles Trait
 *
 * A strict extension of Spatie's Laravel Permission package that enforces entity-scoped roles
 * and completely disables global roles and direct permissions. This trait ensures all role
 * assignments are explicitly tied to specific entities (models), providing fine-grained
 * access control within multi-tenant or multi-entity applications.
 *
 * ## Key Features:
 * - **Entity-Required Roles**: All roles must be assigned to specific entities
 * - **No Global Roles**: Prevents accidental global role assignments
 * - **No Direct Permissions**: Forces role-based permission model
 * - **Strict API**: Clear exceptions guide developers to correct usage
 * - **Multi-Entity Support**: Same user can have different roles on different entities
 *
 * ## Usage Examples:
 *
 * ```php
 * // Assign roles to specific entities
 * $user->assignRole('editor', $project);
 * $user->assignRole(RoleEnum::ADMIN, $organization);
 *
 * // Check entity-specific roles
 * $user->hasRoleOn('editor', $project);
 * $user->hasRoleOn(RoleEnum::ADMIN, $organization);
 *
 * // Get roles for specific entities
 * $projectRoles = $user->rolesOn($project);
 * $orgRoles = $user->rolesOn($organization);
 *
 * // Remove roles from specific entities
 * $user->removeRole('editor', $project);
 *
 * // Multiple role assignment
 * $user->assignRoles(['editor', 'reviewer'], $project);
 * ```
 *
 * ## Disabled Methods:
 * The following Spatie methods are disabled and will throw exceptions:
 * - `assignRole()` without entity parameter
 * - `hasRole()` - use `hasRoleOn($role, $entity)` instead
 * - `givePermissionTo()` - use role-based permissions only
 * - `hasPermissionTo()` - check roles instead of direct permissions
 * - All other direct permission methods
 *
 * ## Database Schema Requirements:
 * Your `model_has_roles` table must include:
 * ```sql
 * $table->ulidMorphs('roleable'); // For entity relationships
 * $table->string('tenant_id')->nullable(); // For multi-tenancy
 * ```
 *
 * ## Migration Example:
 * ```php
 * Schema::table('model_has_roles', function (Blueprint $table) {
 *     $table->ulidMorphs('roleable');
 * });
 * ```
 *
 * @author Your Name
 *
 * @version 1.0.0
 *
 * @since 2025-01-01
 *
 * @requires spatie/laravel-permission ^6.0
 * @requires PHP ^8.1
 * @requires Laravel ^10.0
 *
 * @see https://spatie.be/docs/laravel-permission/v6/introduction
 * @see Spatie\Permission\Traits\HasRoles
 * @see Spatie\Permission\Traits\HasPermissions
 *
 * @method self assignRole(string|int|Role|\BackedEnum $role, Model $entity) Assign role to user on specific entity
 * @method self assignRoles(array $roles, Model $entity) Assign multiple roles to user on specific entity
 * @method self removeRole(string|int|Role|\BackedEnum $role, Model $entity) Remove role from user on specific entity
 * @method bool hasRoleOn(string|int|Role|\BackedEnum $role, Model $entity, ?string $guard = null) Check if user has role on specific entity
 * @method BelongsToMany rolesOn(Model $entity) Get all roles for user on specific entity
 *
 * @throws \BadMethodCallException When using disabled global role methods
 * @throws \InvalidArgumentException When entity parameter is missing from role methods
 * @throws \TypeError When invalid role types are provided
 */
trait HasEntityAwareRoles
{
    use HasRoles {
        assignRole as parentAssignRole;
        removeRole as parentRemoveRole;
        hasRole as parentHasRole;
        roles as parentRoles;
        // Override direct permission methods to disable them
        givePermissionTo as parentGivePermissionTo;
        revokePermissionTo as parentRevokePermissionTo;
        syncPermissions as parentSyncPermissions;
        hasPermissionTo as parentHasPermissionTo;
        hasDirectPermission as parentHasDirectPermission;
        hasAnyPermission as parentHasAnyPermission;
        hasAllPermissions as parentHasAllPermissions;
        permissions as parentPermissions;
    }

    /**
     * Override the roles relationship to include roleable columns in pivot
     */
    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole
        );

        if (! app(PermissionRegistrar::class)->teams) {
            // Add roleable columns to pivot for non-teams setup
            return $relation->withPivot('roleable_type', 'roleable_id');
        }

        $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        $relation->withPivot($teamsKey, 'roleable_type', 'roleable_id');

        // Only apply team filtering if getPermissionsTeamId() returns a valid value
        $teamId = getPermissionsTeamId();
        if ($teamId) {
            $teamField = config('permission.table_names.roles') . '.' . $teamsKey;

            return $relation->wherePivot($teamsKey, $teamId)
                ->where(fn ($q) => $q->whereNull($teamField)->orWhere($teamField, $teamId));
        }

        // If no team ID, return all roles with pivot columns
        return $relation;
    }

    /**
     * Override assignRole to require entity context
     *
     * @param  string|int|Role|\BackedEnum  $role  Single role to assign
     * @param  Model  $entity  Required entity to scope the role to
     * @return $this
     */
    public function assignRole($role, Model $entity)
    {
        $roleIds = $this->collectRoles([$role]);
        $roleId = $roleIds[0]; // Since we only pass one role

        $model = $this->getModel();
        $teamPivot = app(PermissionRegistrar::class)->teams && ! is_a($this, Permission::class) ?
            [app(PermissionRegistrar::class)->teamsKey => getPermissionsTeamId()] : [];

        // Always require entity - no global roles
        $roleablePivot = [
            'roleable_type' => get_class($entity),
            'roleable_id' => $entity->getKey(),
        ];

        $pivotData = array_merge($teamPivot, $roleablePivot);

        if ($model->exists) {
            // Check if this exact role assignment already exists for this entity
            $existingAssignment = $this->roles()
                ->wherePivot('roleable_type', get_class($entity))
                ->wherePivot('roleable_id', $entity->getKey())
                ->where('id', $roleId)
                ->exists();

            if (! $existingAssignment) {
                $this->roles()->attach($roleId, $pivotData);
                $model->unsetRelation('roles');

                if (config('permission.events_enabled')) {
                    event(new RoleAttached($this->getModel(), [$roleId], $entity));
                }
            }
        } else {
            $class = \get_class($model);
            $saved = false;

            $class::saved(
                function ($object) use ($roleId, $model, $pivotData, &$saved) {
                    if ($saved || $model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->roles()->attach($roleId, $pivotData);
                    $model->unsetRelation('roles');
                    $saved = true;
                }
            );
        }

        if (is_a($this, Permission::class)) {
            $this->forgetCachedPermissions();
        }

        return $this;
    }

    /**
     * Override syncRoles to require entity context
     * Remove all current roles on entity and set the given ones.
     *
     * @param  string|int|array|Role|Collection|\BackedEnum  ...$args  Roles to sync, with entity as last parameter
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function syncRoles(...$args)
    {
        // Entity is REQUIRED - check if last argument is a Model
        if (empty($args) || ! end($args) instanceof Model) {
            throw new \InvalidArgumentException(
                'Entity parameter is required. Use: syncRoles($role1, $role2, $entity)'
            );
        }

        $entity = array_pop($args);
        $roles = $args;

        if ($this->getModel()->exists) {
            $this->collectRoles($roles);
            $this->rolesOn($entity)->detach();
            $this->setRelation('roles', collect());
        }

        return $this->assignRoles($roles, $entity);
    }

    /**
     * Assign multiple roles to an entity
     *
     * @param  array  $roles  Array of roles to assign
     * @param  Model  $entity  Required entity to scope the roles to
     * @return $this
     */
    public function assignRoles(array $roles, Model $entity)
    {
        foreach ($roles as $role) {
            $this->assignRole($role, $entity);
        }

        return $this;
    }

    /**
     * Override hasRole to force explicit entity context
     *
     * @throws \BadMethodCallException
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        throw new \BadMethodCallException(
            'hasRole() is disabled in entity-aware context. ' .
            'Use hasRoleOn($role, $entity) for entity-specific checks. '
        );
    }

    /**
     * Override removeRole to require entity context
     *
     * @param  string|int|Role|\BackedEnum  $role
     * @param  Model  $entity  Required entity to scope the removal to
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function removeRole($role, ?Model $entity = null)
    {
        if (! $entity) {
            throw new \InvalidArgumentException(
                'Entity parameter is required. Use: removeRole($role, $entity)'
            );
        }

        $storedRole = $this->getStoredRole($role);

        // Remove role from specific entity only
        $this->roles()
            ->wherePivot('roleable_type', get_class($entity))
            ->wherePivot('roleable_id', $entity->getKey())
            ->detach($storedRole);

        $this->unsetRelation('roles');

        if (is_a($this, Permission::class)) {
            $this->forgetCachedPermissions();
        }

        if (config('permission.events_enabled')) {
            event(new RoleDetached($this->getModel(), $storedRole, $entity));
        }

        return $this;
    }

    /**
     * Get roles scoped to a specific entity
     */
    public function rolesOn(?Model $entity = null): BelongsToMany
    {
        $relation = $this->roles();

        if ($entity) {
            $relation->wherePivot('roleable_type', get_class($entity))
                ->wherePivot('roleable_id', $entity->getKey());
        } else {
            // Global roles (no entity scope)
            $relation->whereNull('roleable_type')
                ->whereNull('roleable_id');
        }

        return $relation;
    }

    /**
     * Check if user has role on specific entity (REQUIRED)
     */
    public function hasRoleOn($roles, Model $entity, ?string $guard = null): bool
    {
        $this->loadMissing('roles');

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        $rolesOnEntity = $this->rolesOn($entity)
            ->when($guard, fn ($q) => $q->where('guard_name', $guard))
            ->get();

        if (is_string($roles)) {
            return $rolesOnEntity->contains('name', $roles);
        }

        if (is_int($roles) || PermissionRegistrar::isUid($roles)) {
            $key = (new ($this->getRoleClass())())->getKeyName();

            return $rolesOnEntity->contains($key, $roles);
        }

        if ($roles instanceof \Spatie\Permission\Contracts\Role) {
            return $rolesOnEntity->contains($roles->getKeyName(), $roles->getKey());
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRoleOn($role, $entity, $guard)) {
                    return true;
                }
            }

            return false;
        }

        if ($roles instanceof \Illuminate\Support\Collection) {
            return $roles->intersect($rolesOnEntity)->isNotEmpty();
        }

        throw new \TypeError('Unsupported type for $roles parameter to hasRoleOn().');
    }

    // =================================================================
    // DISABLED DIRECT PERMISSION METHODS - FORCE ROLE-BASED APPROACH
    // =================================================================

    /**
     * Override to disable direct permissions - use role-based permissions only
     *
     * @throws \BadMethodCallException
     */
    public function givePermissionTo(...$permissions)
    {
        throw new \BadMethodCallException(
            'Direct permissions are disabled. Assign roles to entities instead: assignRole($role, $entity)'
        );
    }

    /**
     * Override to disable direct permissions - use role-based permissions only
     *
     * @throws \BadMethodCallException
     */
    public function revokePermissionTo($permission)
    {
        throw new \BadMethodCallException(
            'Direct permissions are disabled. Remove roles from entities instead: removeRole($role, $entity)'
        );
    }

    /**
     * Override to disable direct permissions - use role-based permissions only
     *
     * @throws \BadMethodCallException
     */
    public function syncPermissions(...$permissions)
    {
        throw new \BadMethodCallException(
            'Direct permissions are disabled. Use role-based permissions: assignRole($role, $entity)'
        );
    }

    /**
     * Override to disable direct permission checks - use role-based checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        throw new \BadMethodCallException(
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.'
        );
    }

    /**
     * Override to disable direct permission checks - use role-based checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasDirectPermission($permission): bool
    {
        throw new \BadMethodCallException(
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.'
        );
    }

    /**
     * Override to disable direct permission checks - use role-based checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasAnyPermission(...$permissions): bool
    {
        throw new \BadMethodCallException(
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.'
        );
    }

    /**
     * Override to disable direct permission checks - use role-based checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasAllPermissions(...$permissions): bool
    {
        throw new \BadMethodCallException(
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.'
        );
    }

    /**
     * Override permissions relationship to disable direct permissions
     *
     * @throws \BadMethodCallException
     */
    public function permissions(): BelongsToMany
    {
        throw new \BadMethodCallException(
            'Direct permissions are disabled. Permissions are managed through roles only.'
        );
    }
}
