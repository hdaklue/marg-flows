<?php

namespace App\Concerns\Roles;

use App\Enums\Role\RoleEnum;
use App\Events\Role\EntityAllRolesRemoved;
use App\Events\Role\EntityBulkRolesUpdated;
use App\Events\Role\EntityRoleAssigned;
use App\Events\Role\EntityRoleRemoved;
use App\Models\User;
use App\Services\Role\RoleCacheService;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roleable Entity Trait - Performance Optimized with Events
 *
 * @method \Illuminate\Database\Eloquent\Relations\Relation participants()
 * @method \Illuminate\Database\Eloquent\Relations\Relation admins()
 * @method \Illuminate\Database\Eloquent\Relations\Relation viewers()
 * @method \Illuminate\Database\Eloquent\Relations\Relation contributors()
 * @method \Illuminate\Database\Eloquent\Relations\Relation managers()
 * @method \Illuminate\Database\Eloquent\Relations\Relation reviewers()
 * @method mixed addParticipant(\Illuminate\Database\Eloquent\Model $user, string|array $roles)
 * @method mixed removeParticipant(\Illuminate\Database\Eloquent\Model $user, ?string $role = null)
 * @method mixed changeParticipantRole(\Illuminate\Database\Eloquent\Model $user, string|\BackedEnum $role)
 * @method bool isParticipant(\Illuminate\Database\Eloquent\Model $user)
 * @method bool isAdmin(\Illuminate\Database\Eloquent\Model $user)
 * @method bool isViewer(\Illuminate\Database\Eloquent\Model $user)
 * @method bool isContributor(\Illuminate\Database\Eloquent\Model $user)
 * @method bool isManager(\Illuminate\Database\Eloquent\Model $user)
 * @method bool isReviewer(\Illuminate\Database\Eloquent\Model $user)
 * @method static \Illuminate\Database\Eloquent\Builder forParticipant(\Illuminate\Database\Eloquent\Model $member)
 *
 * High-performance implementation of entity-aware roles with aggressive caching,
 * query optimization, bulk operations, and comprehensive event system.
 * Maintains strict entity-scoped role enforcement while delivering maximum
 * performance for large-scale applications.
 *
 * ## Performance Features:
 * - **Centralized Caching**: Uses RoleCacheService for all cache operations
 * - **Event-Driven**: Fires events for all role changes with automatic cache invalidation
 * - **Optimized Queries**: Single queries for bulk operations, eager loading
 * - **Index-Friendly**: Designed for optimal database index usage
 * - **Lazy Loading**: Deferred execution for expensive operations
 * - **Memory Efficient**: Minimal object instantiation and collection reuse
 *
 * ## Events Fired:
 * - `EntityRoleAssigned`: When a role is assigned to a user
 * - `EntityRoleRemoved`: When a role is removed from a user
 * - `EntityAllRolesRemoved`: When all roles are removed from a user
 * - `EntityBulkRolesUpdated`: When bulk role operations are performed
 *
 * ## Usage Examples:
 *
 * ```php
 * // All operations now fire events and use cached data
 * $admins = $project->usersWithRole('admin'); // Uses RoleCacheService
 * $project->assignUserRole($user, 'admin'); // Fires EntityRoleAssigned event
 * $project->removeAllUserRoles($user); // Fires EntityAllRolesRemoved event
 * $project->syncAllUserRoles($userRoleMap); // Fires EntityBulkRolesUpdated event
 *
 * // Performance-aware checks with caching
 * $project->userHasRole($user, 'admin'); // Uses cached data
 * $project->getUserRolesSummary(); // Cached summary data
 * ```
 *
 * ## Cache Integration:
 * - All cache operations delegated to RoleCacheService
 * - Automatic cache invalidation on role changes
 * - Event listeners can warm cache for related data
 * - Configurable TTL per operation
 *
 * @author Hassan Ibrahim
 *
 * @version 2.1.0 - Service Integration & Events *
 *
 * @see RoleableEntity For the primary implementation trait
 * @see \Spatie\Permission\Models\Role For underlying role model
 * @since 2025-06-16
 */
trait RoleableEntity
{
    /**
     * Role caching service instance
     */
    protected ?RoleCacheService $roleCacheService = null;

    /**
     * Get entities for a specific user
     */
    public function scopeForParticipant(Builder $query, Model $member): Builder
    {
        return $query->whereAttachedTo($member, 'participants');
    }

    // ========================================================================
    // Boolean Check Methods
    // ========================================================================

    /**
     * Check if a user is a participant (has any role) on this entity
     */
    public function isParticipant(Model $user): bool
    {
        return $this->userHasAnyRole($user);
    }

    /**
     * Check if a user is an admin of this entity
     */
    public function isAdmin(Model $user): bool
    {
        return $this->userHasRole($user, RoleEnum::ADMIN->value);
    }

    /**
     * Check if a user is a viewer of this entity
     */
    public function isViewer(Model $user): bool
    {
        return $this->userHasRole($user, RoleEnum::VIEWER->value);
    }

    /**
     * Check if a user is a reviewer of this entity
     */
    public function isReviewer(Model $user): bool
    {
        return $this->userHasRole($user, RoleEnum::ADMIN->value) || $this->userHasRole($user, RoleEnum::MANAGER->value);
    }

    /**
     * Check if a user is a manager of this entity
     */
    public function isManager(Model $user): bool
    {
        return $this->userHasRole($user, 'manager');
    }

    /**
     * Check if a user is a contributor of this entity
     */
    public function isContributor(Model $user): bool
    {
        return $this->userHasRole($user, 'contributor');
    }

    // ========================================================================
    // Query Builder Methods (for flexibility and performance)
    // ========================================================================

    /**
     * Get participants query builder for additional filtering
     */
    public function participants(): MorphToMany
    {
        return $this->usersWithAnyRole();
    }

    /**
     * Get admins query builder for additional filtering
     */
    public function admins(): MorphToMany
    {
        return $this->usersWithRole(RoleEnum::ADMIN->value);
    }

    public function managers(): MorphToMany
    {
        return $this->usersWithRole(RoleEnum::MANAGER->value);
    }

    public function contributors(): MorphToMany
    {
        return $this->usersWithRole(RoleEnum::CONTRIBUTOR->value);
    }

    public function reviewers(): MorphToMany
    {
        return $this->usersWithRole([RoleEnum::ADMIN->value, RoleEnum::MANAGER->value, RoleEnum::SUPER_ADMIN->value]);
    }

    /**
     * Get viewers query builder for additional filtering
     */
    public function viewers(): MorphToMany
    {
        return $this->usersWithRole(RoleEnum::VIEWER->value);
    }

    // ========================================================================
    // Convenience Methods for Common Role Operations
    // ========================================================================

    /**
     * Add a participant with specific role(s)
     */
    public function addParticipant(Model $user, string|array $roles, bool $silently = false): self
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->assignUserRole($user, $roles, $silently);
    }

    /**
     * Remove a participant's specific role(s)
     */
    public function removeParticipant(Model $user, ?string $role = null): self
    {
        if ($role) {
            return $this->removeUserRole($user, $role);
        }

        return $this->removeAllUserRoles($user);
    }

    /**
     * Change a participant's role(s)
     * this will revoke all roles and assign given role
     */
    public function changeParticipantRole(Model $user, string|BackedEnum $role): self
    {
        DB::transaction(function () use ($user, $role) {
            if ($this->userHasAnyRole($user)) {
                $this->removeAllUserRoles($user);
            }
            $this->assignUserRole($user, $role);
        });

        return $this;
    }

    /**
     * Roles asigned to this entity
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'roleable',
            config('permission.table_names.model_has_roles'),

        );
    }

    /**
     * Get users who have specific roles on this entity (Performance Optimized)
     *
     * @param  string|array|Role|\BackedEnum  $roles  Role(s) to filter by
     * @param  string|null  $guard  Guard name (optional)
     * @param  bool  $withRoles  Whether to eager load roles (default: false)
     */
    public function usersWithRole($roles, ?string $guard = null): MorphToMany
    {
        $userModel = config('auth.providers.users.model');
        $guard = $guard ?: config('auth.defaults.guard');

        // Build base relation with optimal column selection
        $relation = $this->morphToMany(
            $userModel,
            'roleable',
            config('permission.table_names.model_has_roles'),
            'roleable_id',
            config('permission.column_names.model_morph_key')
        )
            ->withPivot(['role_id'])
            ->select([
                config('permission.table_names.users') . '.id',
                config('permission.table_names.users') . '.name',
                config('permission.table_names.users') . '.email',
            ]);

        // Add team support with optimal indexing
        if (app(PermissionRegistrar::class)->teams) {
            $teamsKey = app(PermissionRegistrar::class)->teamsKey;
            $relation->withPivot([$teamsKey]);

            $teamId = getPermissionsTeamId();
            if ($teamId) {
                $relation->wherePivot($teamsKey, $teamId);
            }
        }

        // Optimize role filtering with cached role IDs
        if (! empty($roles)) {
            $roleIds = $this->getRoleCacheService()->getCachedRoleIds(
                is_array($roles) ? $roles : [$roles],
                $guard
            );

            if (! empty($roleIds)) {
                $relation->whereIn(config('permission.table_names.model_has_roles') . '.role_id', $roleIds);
            } else {
                // No matching roles found - return empty result efficiently
                $relation->whereRaw('0 = 1');
            }
        }

        // Eager load roles if requested
        // if ($withRoles) {
        //     $relation->with([
        //         'roles' => function ($query) use ($guard) {
        //             $query->select(['id', 'name', 'guard_name'])
        //                 ->wherePivot('roleable_type', $this->getMorphClass())
        //                 ->wherePivot('roleable_id', $this->getKey());
        //             if ($guard) {
        //                 $query->where('guard_name', $guard);
        //             }

        //             // Apply team constraint if teams are enabled
        //             if (app(PermissionRegistrar::class)->teams) {
        //                 $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        //                 $teamId = getPermissionsTeamId();
        //                 if ($teamId) {
        //                     $query->wherePivot($teamsKey, $teamId);
        //                 }
        //             }
        //         },
        //     ]);
        // }

        return $relation;
    }

    /**
     * Get all users with any role on this entity (Performance Optimized)
     */
    public function usersWithAnyRole(?string $guard = null)
    {
        return $this->usersWithRole([], $guard)
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select([
                'users.*',
                'roles.name as role_name',
                'roles.id as role_id',
                'roles.guard_name as role_guard',
            ]);
    }

    /**
     * Get cached list of users with specific role (Common Usage Pattern)
     */
    public function getCachedUsersWithRole($roles, ?string $guard = null): Collection
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->getRoleCacheService()->getEntityCacheKey($this) . ':users_with_role:' . md5(serialize($roles) . $guard);

        return $this->getRoleCacheService()->remember($cacheKey, function () use ($roles, $guard) {
            return $this->usersWithRole($roles, $guard)->get(['id', 'name', 'email']);
        });
    }

    /**
     * Get cached count of users with specific role (Common Usage Pattern)
     */
    public function getCachedUsersWithRoleCount($roles, ?string $guard = null): int
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->getRoleCacheService()->getEntityCacheKey($this) . ':users_with_role_count:' . md5(serialize($roles) . $guard);

        return $this->getRoleCacheService()->remember($cacheKey, function () use ($roles, $guard) {
            return $this->usersWithRole($roles, $guard)->count();
        });
    }

    /**
     * Get cached list of all users with any role (Common Usage Pattern)
     */
    public function getCachedUsersWithAnyRole(?string $guard = null): Collection
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->getRoleCacheService()->getEntityCacheKey($this) . ':all_users_with_roles:' . $guard;

        return $this->getRoleCacheService()->remember($cacheKey, function () use ($guard) {
            return $this->usersWithAnyRole($guard)->get(['id', 'name', 'email']);
        });
    }

    /**
     * Check if a user has a specific role on this entity (Performance Optimized)
     */
    public function userHasRole(Model $user, $role, ?string $guard = null): bool
    {
        if (! $user->exists) {
            return false;
        }

        $guard = $guard ?: config('auth.defaults.guard');

        // Get cached user roles for this entity
        $userRoleIds = $this->getRoleCacheService()->cacheUserRoles($user, $this, $guard);

        if (empty($userRoleIds)) {
            return false;
        }

        // Check if requested role ID is in user's roles
        $targetRoleIds = $this->getRoleCacheService()->getCachedRoleIds([$role], $guard);

        return ! empty(array_intersect($userRoleIds, $targetRoleIds));
    }

    /**
     * Check if a user has any role on this entity (Performance Optimized)
     */
    public function userHasAnyRole(Model $user): bool
    {
        if (! $user->exists) {
            return false;
        }

        return $this->getRoleCacheService()->cacheUserRoleExists($user, $this);
    }

    /**
     * get entiti scoped to user
     */
    public function scopeForUser(Builder $query, Model $user): Builder
    {
        return $query->whereAttachedTo($user, 'usersWithAnyRole');
    }

    /**
     * Assign a role to a user on this entity (Performance Optimized with Events)
     */
    public function assignUserRole(Model $user, $role, bool $silently = false): self
    {
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role, $this);

            // Invalidate cache and fire event
            $this->getRoleCacheService()->invalidateUserRoleCache($user, $this);

            if (! $silently) {
                EntityRoleAssigned::dispatch($user, $this, $role);
            }
        } else {
            throw new \BadMethodCallException(
                'User model must use HasEntityAwareRoles trait to support entity-scoped roles.'
            );
        }

        return $this;
    }

    /**
     * Assign multiple roles to a user on this entity (Performance Optimized with Events)
     */
    public function assignUserRoles(Model $user, array $roles, bool $silently = false): self
    {
        if (empty($roles)) {
            return $this;
        }

        DB::transaction(function () use ($user, $roles, $silently) {
            foreach ($roles as $role) {
                $this->assignUserRole($user, $role, $silently);
            }
        });

        return $this;
    }

    /**
     * Remove a role from a user on this entity (Performance Optimized with Events)
     */
    public function removeUserRole(Model $user, $role): self
    {
        if (method_exists($user, 'removeRole')) {
            $user->removeRole($role, $this);

            // Invalidate cache and fire event
            $this->getRoleCacheService()->invalidateUserRoleCache($user, $this);

            if (config('permission.events_enabled', true)) {
                EntityRoleRemoved::dispatch($user, $this, $role);
            }
        } else {
            throw new \BadMethodCallException(
                'User model must use HasEntityAwareRoles trait to support entity-scoped roles.'
            );
        }

        return $this;
    }

    /**
     * Remove all roles from a user on this entity (Performance Optimized with Events)
     */
    public function removeAllUserRoles(Model $user): self
    {
        // Get current roles before removal for event
        $currentRoles = $this->rolesForUser($user);

        DB::transaction(function () use ($user) {
            DB::table(config('permission.table_names.model_has_roles'))
                ->where('roleable_type', $this->getMorphClass())
                ->where('roleable_id', $this->getKey())
                ->where(config('permission.column_names.model_morph_key'), $user->getKey())
                ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                    $teamId = getPermissionsTeamId();
                    if ($teamId) {
                        $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                    }
                })
                ->delete();
        });

        // Invalidate cache and fire event
        $this->getRoleCacheService()->invalidateUserRoleCache($user, $this);

        if (config('permission.events_enabled', true) && $currentRoles->isNotEmpty()) {
            EntityAllRolesRemoved::dispatch($user, $this);
        }

        return $this;
    }

    /**
     * Bulk sync user roles for this entity (Performance Optimized with Events)
     */
    public function syncAllUserRoles(array $userRoleMap): self
    {
        DB::transaction(function () use ($userRoleMap) {
            foreach ($userRoleMap as $user => $roles) {
                if ($user instanceof Model) {
                    $this->removeAllUserRoles($user);
                    if (! empty($roles)) {
                        $this->assignUserRoles($user, $roles);
                    }
                }
            }
        });

        // Fire bulk update event
        if (config('permission.events_enabled', true)) {
            EntityBulkRolesUpdated::dispatch($this, $userRoleMap);
        }

        return $this;
    }

    /**
     * Get all roles for a specific user on this entity (Performance Optimized)
     */
    public function rolesForUser(Model $user): Collection
    {
        if (! $user->exists) {
            return collect();
        }

        if (method_exists($user, 'rolesOn')) {
            return $user->rolesOn($this)->get(['id', 'name', 'guard_name']);
        }

        // Fallback with cached query
        return $this->getRoleCacheService()->cacheUserRolesCollection($user, $this);
    }

    /**
     * Get role names for a specific user on this entity (Performance Optimized)
     */
    public function roleNamesForUser(Model $user): Collection
    {
        return $this->rolesForUser($user)->pluck('name');
    }

    /**
     * Scope to find entities where a user has specific role(s) (Performance Optimized)
     */
    public function scopeWhereUserHasRole(Builder $query, Model $user, $roles, ?string $guard = null): Builder
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $roleIds = $this->getRoleCacheService()->getCachedRoleIds(
            is_array($roles) ? $roles : [$roles],
            $guard
        );

        if (empty($roleIds)) {
            return $query->whereRaw('0 = 1'); // No matching roles
        }

        return $query->whereExists(function ($subQuery) use ($user, $roleIds) {
            $subQuery->select(DB::raw(1))
                ->from(config('permission.table_names.model_has_roles'))
                ->whereColumn('roleable_id', $this->getTable() . '.id')
                ->where('roleable_type', $this->getMorphClass())
                ->where(config('permission.column_names.model_morph_key'), $user->getKey())
                ->whereIn('role_id', $roleIds)
                ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                    $teamId = getPermissionsTeamId();
                    if ($teamId) {
                        $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                    }
                });
        });
    }

    /**
     * Scope to find entities where a user has any role (Performance Optimized)
     */
    public function scopeWhereUserHasAnyRole(Builder $query, Model $user): Builder
    {
        return $query->whereExists(function ($subQuery) use ($user) {
            $subQuery->select(DB::raw(1))
                ->from(config('permission.table_names.model_has_roles'))
                ->whereColumn('roleable_id', $this->getTable() . '.id')
                ->where('roleable_type', $this->getMorphClass())
                ->where(config('permission.column_names.model_morph_key'), $user->getKey())
                ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                    $teamId = getPermissionsTeamId();
                    if ($teamId) {
                        $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                    }
                });
        });
    }

    /**
     * Scope to find entities where a user does NOT have any roles (Performance Optimized)
     */
    public function scopeWhereUserHasNoRoles(Builder $query, Model $user): Builder
    {
        return $query->whereNotExists(function ($subQuery) use ($user) {
            $subQuery->select(DB::raw(1))
                ->from(config('permission.table_names.model_has_roles'))
                ->whereColumn('roleable_id', $this->getTable() . '.id')
                ->where('roleable_type', $this->getMorphClass())
                ->where(config('permission.column_names.model_morph_key'), $user->getKey())
                ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                    $teamId = getPermissionsTeamId();
                    if ($teamId) {
                        $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                    }
                });
        });
    }

    /**
     * Get count of users with any role on this entity (Performance Optimized)
     */
    public function getUsersWithRoleCount(): int
    {
        return $this->getRoleCacheService()->cacheEntityUsersCount($this);
    }

    /**
     * Get count of users with specific role on this entity (Performance Optimized)
     */
    public function getUsersWithSpecificRoleCount($role, ?string $guard = null): int
    {
        $guard = $guard ?: config('auth.defaults.guard');

        return $this->getRoleCacheService()->cacheEntityRoleCount($this, $role, $guard);
    }

    /**
     * Get unique roles assigned on this entity (Performance Optimized)
     */
    public function getAssignedRoles(): Collection
    {
        return $this->getRoleCacheService()->cacheAssignedRoles($this);
    }

    /**
     * Get users with roles and pivot data as a structured array (Performance Optimized)
     */
    public function getUserRolesSummary($roles = null, ?string $guard = null): array
    {
        $guard = $guard ?: config('auth.defaults.guard');

        return $this->getRoleCacheService()->cacheUserRolesSummary($this, $roles, $guard);
    }

    /**
     * Clear all caches for this entity
     */
    public function clearRoleCache(): void
    {
        $this->getRoleCacheService()->invalidateEntityCache($this);
    }

    /**
     * Warm cache for this entity with optional users
     */
    public function warmRoleCache(array $users = [], ?string $guard = null): void
    {
        $this->getRoleCacheService()->warmCache($this, $users, $guard);
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
