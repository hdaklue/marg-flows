<?php

declare(strict_types=1);

namespace App\Concerns\Roles;

use App\Events\Role\EntityRoleAssigned;
use App\Events\Role\EntityRoleRemoved;
use App\Events\Role\EntityRolesSynced;
use App\Services\Role\RoleCacheService;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

/**
 * Entity-Aware Roles Trait with Service Integration and Events
 *
 * A strict extension of Spatie's Laravel Permission package that enforces entity-scoped roles
 * and completely disables global roles and direct permissions. Enhanced with centralized
 * caching service integration and comprehensive event system for all role operations.
 *
 * ## Key Features:
 * - **Entity-Required Roles**: All roles must be assigned to specific entities
 * - **No Global Roles**: Prevents accidental global role assignments
 * - **No Direct Permissions**: Forces role-based permission model
 * - **Service Integration**: Uses RoleCacheService for all cache operations
 * - **Event-Driven**: Fires events for all role changes
 * - **Strict API**: Clear exceptions guide developers to correct usage
 * - **Multi-Entity Support**: Same user can have different roles on different entities
 *
 * ## Events Fired:
 * - `EntityRoleAssigned`: When a role is assigned to a user on an entity
 * - `EntityRoleRemoved`: When a role is removed from a user on an entity
 * - `EntityRolesSynced`: When user roles are synced on an entity
 * - Plus original Spatie events: `RoleAttached`, `RoleDetached`
 *
 * ## Usage Examples:
 *
 * ```php
 * // All operations fire events and use cached data
 * $user->assignRole('editor', $project); // Fires EntityRoleAssigned
 * $user->removeRole('editor', $project); // Fires EntityRoleRemoved
 * $user->syncRoles(['admin', 'editor'], $project); // Fires EntityRolesSynced
 *
 * // Check entity-specific roles with caching
 * $user->hasRoleOn('editor', $project); // Uses cached data
 * $projectRoles = $user->rolesOn($project); // Cached collection
 * ```
 *
 * ## Cache Integration:
 * - All cache operations delegated to RoleCacheService
 * - Automatic cache invalidation on role changes
 * - Event listeners can warm cache for related data
 * - Configurable TTL per operation
 *
 * ## Disabled Methods:
 * The following Spatie methods are disabled and will throw exceptions:
 * - `assignRole()` without entity parameter
 * - `hasRole()` - use `hasRoleOn($role, $entity)` instead
 * - `givePermissionTo()` - use role-based permissions only
 * - `hasPermissionTo()` - check roles instead of direct permissions
 * - All other direct permission methods
 *
 * @author Hassan Ibrahim
 *
 * @version 1.1.0 - Service Integration & Events
 *
 * @since 2025-06-16
 */
trait HasEntityAwareRoles
{
    // TODO: implement silently option
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
     * Role caching service instance
     */
    protected ?RoleCacheService $roleCacheService = null;

    /**
     * Override the roles relationship to include roleable columns in pivot
     */
    public function roles(): MorphToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole,
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
     * Override assignRole to require entity context and fire events
     *
     * @param  string|int|Role|\BackedEnum  $role  Single role to assign
     * @param  Model  $entity  Required entity to scope the role to
     * @return $this
     */
    public function assignRole(mixed $role, Model $entity)
    {
        if ($role instanceof \BackedEnum) {
            $role = $role->value;
        }

        $roleIds = $this->collectRoles([$role]);
        $roleId = $roleIds[0]; // Since we only pass one role

        $model = $this->getModel();
        $teamPivot = app(PermissionRegistrar::class)->teams && ! is_a($this, Permission::class) ?
            [app(PermissionRegistrar::class)->teamsKey => getPermissionsTeamId()] : [];

        // Always require entity - no global roles
        $roleablePivot = [
            'roleable_type' => $entity->getMorphClass(),
            'roleable_id' => $entity->getKey(),
        ];

        $pivotData = array_merge($teamPivot, $roleablePivot);

        if ($model->exists) {
            // Check if this exact role assignment already exists for this entity
            $existingAssignment = $this->roles()
                ->wherePivot('roleable_type', $entity->getMorphClass())
                ->wherePivot('roleable_id', $entity->getKey())
                ->where('id', $roleId)
                ->exists();

            if (! $existingAssignment) {
                $this->roles()->attach($roleId, $pivotData);
                $model->unsetRelation('roles');

                // Invalidate cache
                $this->getRoleCacheService()->invalidateUserRoleCache($this, $entity);

                // Fire events
                if (config('permission.events_enabled')) {
                    // event(new RoleAttached($this->getModel(), [$roleId], $entity));
                    EntityRoleAssigned::dispatch($this, $entity, $role);
                }
            }
        } else {
            $class = \get_class($model);
            $saved = false;

            $class::saved(
                function ($object) use ($roleId, $model, $pivotData, $entity, &$saved) {
                    if ($saved || $model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->roles()->attach($roleId, $pivotData);
                    $model->unsetRelation('roles');

                    // Invalidate cache
                    $this->getRoleCacheService()->invalidateUserRoleCache($model, $entity);

                    // Fire events
                    // if (config('permission.events_enabled')) {
                    //     EntityRoleAssigned::dispatch($model, $entity, $role);
                    // }

                    $saved = true;
                },
            );
        }

        if (is_a($this, Permission::class)) {
            $this->forgetCachedPermissions();
        }

        return $this;
    }

    /**
     * Override syncRoles to require entity context and fire events
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
        throw_if(empty($args) || ! end($args) instanceof Model, new \InvalidArgumentException(
            'Entity parameter is required. Use: syncRoles($role1, $role2, $entity)',
        ));

        $entity = array_pop($args);
        $roles = $args;

        // Get current roles for event
        $oldRoles = $this->rolesOn($entity)->pluck('name')->toArray();

        if ($this->getModel()->exists) {
            $this->collectRoles($roles);
            $this->rolesOn($entity)->detach();
            $this->setRelation('roles', collect());

            // Invalidate cache after detaching
            $this->getRoleCacheService()->invalidateUserRoleCache($this, $entity);
        }

        // Assign new roles
        $assignedRoles = $this->assignRoles($roles, $entity);

        // Fire sync event
        if (config('permission.events_enabled', true)) {
            $newRoles = is_array($roles) ? $roles : [$roles];
            EntityRolesSynced::dispatch($this, $entity, $oldRoles, $newRoles);
        }

        return $assignedRoles;
    }

    /**
     * Assign multiple roles to an entity with events
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
    public function hasRole($roles, ?string $guard = null): never
    {
        throw new \BadMethodCallException(
            'hasRole() is disabled in entity-aware context. ' .
            'Use hasRoleOn($role, $entity) for entity-specific checks. ',
        );
    }

    /**
     * Override removeRole to require entity context and fire events
     *
     * @param  string|int|Role|\BackedEnum  $role
     * @param  Model  $entity  Required entity to scope the removal to
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function removeRole($role, ?Model $entity = null)
    {
        throw_unless($entity, new \InvalidArgumentException(
            'Entity parameter is required. Use: removeRole($role, $entity)',
        ));

        $storedRole = $this->getStoredRole($role);

        // Remove role from specific entity only
        $this->roles()
            ->wherePivot('roleable_type', $entity->getMorphClass())
            ->wherePivot('roleable_id', $entity->getKey())
            ->detach($storedRole);

        $this->unsetRelation('roles');

        // Invalidate cache
        $this->getRoleCacheService()->invalidateUserRoleCache($this, $entity);

        if (is_a($this, Permission::class)) {
            $this->forgetCachedPermissions();
        }

        // Fire events
        if (config('permission.events_enabled')) {
            // event(new RoleDetached($this->getModel(), $storedRole, $entity));
            EntityRoleRemoved::dispatch($this, $entity, $role);
        }

        return $this;
    }

    /**
     * Get roles scoped to a specific entity
     */
    #[Scope]
    public function rolesOn(Model $entity): BelongsToMany
    {
        return $this->roles()->wherePivot('roleable_type', '=', $entity->getMorphClass())
            ->wherePivot('roleable_id', $entity->getKey());
    }

    /**
     * Check if user has any roles at all on specific entity (REQUIRED) with caching
     *
     * @param  Model  $entity  Required entity to scope the check to
     */
    public function hasAnyRoleOn(Model $entity): bool
    {
        return $this->getRoleCacheService()->cacheUserRoleExists($this, $entity);
    }

    /**
     * Check if user has role on specific entity (REQUIRED) with caching
     */
    public function hasRoleOn($roles, Model $entity, ?string $guard = null): bool
    {
        $guard = $guard ?: config('auth.defaults.guard');

        // Use cached user roles for this entity
        $userRoleIds = $this->getRoleCacheService()->cacheUserRoles($this, $entity, $guard);

        if (empty($userRoleIds)) {
            return false;
        }

        // Handle different role types
        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        // Get cached role IDs for comparison
        $targetRoleIds = $this->getRoleCacheService()->getCachedRoleIds(
            is_array($roles) ? $roles : [$roles],
            $guard,
        );

        return ! empty(array_intersect($userRoleIds, $targetRoleIds));
    }

    /**
     * Get cached roles collection for specific entity
     */
    public function getCachedRolesOn(Model $entity, ?string $guard = null): Collection
    {
        return $this->getRoleCacheService()->cacheUserRolesCollection($this, $entity, $guard);
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
            'Direct permissions are disabled. Assign roles to entities instead: assignRole($role, $entity)',
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
            'Direct permissions are disabled. Remove roles from entities instead: removeRole($role, $entity)',
        );
    }

    /**
     * Override to disable direct permissions - use role-based permissions only
     */
    public function getDirectPermissions(): never
    {
        throw new \BadMethodCallException(
            'Direct permissions are disabled. Use role-based permissions: assignRole($role, $entity)',
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
            'Direct permissions are disabled. Use role-based permissions: assignRole($role, $entity)',
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
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.',
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
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.',
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
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.',
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
            'Direct permission checks are disabled. Use hasRoleOn($role, $entity) instead.',
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
            'Direct permissions are disabled. Permissions are managed through roles only.',
        );
    }

    // =================================================================
    // DISABLED GLOBAL ROLE METHODS - FORCE ENTITY-SCOPED APPROACH
    // =================================================================

    /**
     * Override to disable global role checks - use entity-scoped checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasAnyRole(...$roles): bool
    {
        throw new \BadMethodCallException(
            'Global role checks are disabled. Use hasRoleOn($role, $entity) instead.',
        );
    }

    /**
     * Override to disable global role checks - use entity-scoped checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        throw new \BadMethodCallException(
            'Global role checks are disabled. Use hasRoleOn($role, $entity) instead.',
        );
    }

    /**
     * Override to disable global role checks - use entity-scoped checks only
     *
     * @throws \BadMethodCallException
     */
    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        throw new \BadMethodCallException(
            'Global role checks are disabled. Use hasRoleOn($role, $entity) instead.',
        );
    }

    /**
     * Override to disable global role name retrieval - use entity-scoped instead
     *
     * @throws \BadMethodCallException
     */
    public function getRoleNames(): Collection
    {
        throw new \BadMethodCallException(
            'Global role names are disabled. Use rolesOn($entity)->pluck(\'name\') instead.',
        );
    }

    /**
     * Override to disable global role queries - use entity-scoped queries instead
     *
     * @throws \BadMethodCallException
     */
    public function scopeRole(Builder $query, $roles, $guard = null, $without = false): never
    {
        throw new \BadMethodCallException(
            'Global role scopes are disabled. Create custom entity-aware scopes instead.',
        );
    }

    /**
     * Override to disable global role queries - use entity-scoped queries instead
     *
     * @throws \BadMethodCallException
     */
    public function scopeWithoutRole(Builder $query, $roles, $guard = null): never
    {
        throw new \BadMethodCallException(
            'Global role scopes are disabled. Create custom entity-aware scopes instead.',
        );
    }

    // =================================================================
    // CACHE MANAGEMENT METHODS
    // =================================================================

    /**
     * Clear user role cache for specific entity
     *
     * @param  Model  $entity  The entity to clear cache for
     * @param  string|null  $guard  Guard name
     */
    public function clearRoleCacheFor(Model $entity, ?string $guard = null): void
    {
        $this->getRoleCacheService()->invalidateUserRoleCache($this, $entity, $guard);
    }

    /**
     * Warm user role cache for specific entity
     *
     * @param  Model  $entity  The entity to warm cache for
     * @param  string|null  $guard  Guard name
     */
    public function warmRoleCacheFor(Model $entity, ?string $guard = null): void
    {
        // Pre-load commonly accessed data
        $this->getRoleCacheService()->cacheUserRoles($this, $entity, $guard);
        $this->getRoleCacheService()->cacheUserRoleExists($this, $entity, $guard);
        $this->getRoleCacheService()->cacheUserRolesCollection($this, $entity, $guard);
    }

    /**
     * Get cache statistics for debugging
     *
     * @param  Model  $entity  The entity to get stats for
     * @param  string|null  $guard  Guard name
     * @return array Cache hit/miss statistics
     */
    public function getRoleCacheStats(Model $entity, ?string $guard = null): array
    {
        // This would require cache driver that supports statistics
        // For now, return basic information about cache keys
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheService = $this->getRoleCacheService();

        return [
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'user_id' => $this->getKey(),
            'guard' => $guard,
            'cache_ttl' => $cacheService->getTtl(),
            'cache_keys' => [
                'user_roles' => 'Generated on demand',
                'user_exists' => 'Generated on demand',
                'user_collection' => 'Generated on demand',
            ],
        ];
    }

    /**
     * Get the role caching service instance
     */
    protected function getRoleCacheService(): RoleCacheService
    {
        if (! $this->roleCacheService) {
            $this->roleCacheService = app(RoleCacheService::class);
        }

        return $this->roleCacheService;
    }
}
