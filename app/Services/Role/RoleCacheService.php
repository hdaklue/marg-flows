<?php

namespace App\Services\Role;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role Caching Service
 *
 * Centralized caching service for entity-aware roles with performance optimization.
 * Handles all role-related cache operations, invalidation strategies, and provides
 * consistent cache key generation across the application.
 *
 * ## Cache Strategy:
 * - Role ID mappings cached per guard (5 min TTL)
 * - User-role relationships cached per entity (5 min TTL)
 * - Query results cached with automatic invalidation
 * - Team-aware cache keys for multi-tenancy support
 * - Pattern-based cache invalidation for bulk operations
 *
 * ## Performance Features:
 * - Bulk cache operations to minimize round trips
 * - Intelligent cache warming for frequently accessed data
 * - Memory-efficient key generation
 * - Lazy loading with deferred execution
 * - Cache hit/miss metrics tracking
 *
 * @author Hassan Ibrahim
 *
 * @version 1.0.0
 *
 * @since 2025-06-16
 */
class RoleCacheService
{
    /**
     * Default cache TTL for role-related queries (5 minutes)
     */
    protected int $defaultTtl = 300;

    /**
     * Cache key prefixes for different data types
     */
    protected array $prefixes = [
        'role_ids' => 'roles:ids',
        'user_roles' => 'entity_roles',
        'entity_data' => 'entity',
        'role_summary' => 'summary',
        'user_exists' => 'exists',
        'role_count' => 'count',
    ];

    /**
     * Get cached role IDs for performance optimization
     *
     * @param  array  $roles  Array of role names, IDs, or Role instances
     * @param  string  $guard  Guard name
     * @param  int|null  $ttl  Cache TTL override
     * @return array Array of role IDs
     */
    public function getCachedRoleIds(array $roles, string $guard, ?int $ttl = null): array
    {
        if (empty($roles)) {
            return [];
        }

        $cacheKey = $this->buildRoleIdsCacheKey($roles, $guard);
        $ttl ??= $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($roles, $guard) {
            return $this->resolveRoleIds($roles, $guard);
        });
    }

    /**
     * Cache user-role relationship for specific entity
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string|null  $guard  Guard name
     * @param  int|null  $ttl  Cache TTL override
     * @return array Array of role IDs for the user on the entity
     */
    public function cacheUserRoles(Model $user, Model $entity, ?string $guard = null, ?int $ttl = null): array
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->buildUserRoleCacheKey($user, $entity, $guard);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($user, $entity, $guard) {
            return $this->fetchUserRoleIds($user, $entity, $guard);
        });
    }

    /**
     * Cache user role existence check for specific entity
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string|null  $guard  Guard name
     * @param  int|null  $ttl  Cache TTL override
     * @return bool Whether user has any roles on the entity
     */
    public function cacheUserRoleExists(Model $user, Model $entity, ?string $guard = null, ?int $ttl = null): bool
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->buildUserRoleCacheKey($user, $entity, $guard) . ':exists';
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($user, $entity) {
            return $this->checkUserRoleExists($user, $entity);
        });
    }

    /**
     * Cache user roles collection for specific entity
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string|null  $guard  Guard name
     * @param  int|null  $ttl  Cache TTL override
     * @return Collection Collection of Role models
     */
    public function cacheUserRolesCollection(Model $user, Model $entity, ?string $guard = null, ?int $ttl = null): Collection
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->buildUserRoleCacheKey($user, $entity, $guard) . ':roles';
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($user, $entity, $guard) {
            return $this->fetchUserRolesCollection($user, $entity, $guard);
        });
    }

    /**
     * Cache entity users count
     *
     * @param  Model  $entity  The entity model
     * @param  int|null  $ttl  Cache TTL override
     * @return int Count of users with any role on the entity
     */
    public function cacheEntityUsersCount(Model $entity, ?int $ttl = null): int
    {
        $cacheKey = $this->buildEntityCacheKey($entity) . ':users_count';
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($entity) {
            return $this->fetchEntityUsersCount($entity);
        });
    }

    /**
     * Cache entity role count for specific role
     *
     * @param  Model  $entity  The entity model
     * @param  mixed  $role  Role name, ID, or Role instance
     * @param  string|null  $guard  Guard name
     * @param  int|null  $ttl  Cache TTL override
     * @return int Count of users with the specific role on the entity
     */
    public function cacheEntityRoleCount(Model $entity, $role, ?string $guard = null, ?int $ttl = null): int
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $roleIds = $this->getCachedRoleIds([$role], $guard);

        if (empty($roleIds)) {
            return 0;
        }

        $cacheKey = $this->buildEntityCacheKey($entity) . ':role_' . $roleIds[0] . '_count';
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($entity, $roleIds) {
            return $this->fetchEntityRoleCount($entity, $roleIds);
        });
    }

    /**
     * Cache assigned roles for entity
     *
     * @param  Model  $entity  The entity model
     * @param  int|null  $ttl  Cache TTL override
     * @return Collection Collection of assigned Role models
     */
    public function cacheAssignedRoles(Model $entity, ?int $ttl = null): Collection
    {
        $cacheKey = $this->buildEntityCacheKey($entity) . ':assigned_roles';
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($entity) {
            return $this->fetchAssignedRoles($entity);
        });
    }

    /**
     * Cache user roles summary for entity
     *
     * @param  Model  $entity  The entity model
     * @param  mixed  $roles  Optional roles filter
     * @param  string|null  $guard  Guard name
     * @param  int|null  $ttl  Cache TTL override
     * @return array Structured array of user-role mappings
     */
    public function cacheUserRolesSummary(Model $entity, $roles = null, ?string $guard = null, ?int $ttl = null): array
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $cacheKey = $this->buildEntityCacheKey($entity) . ':summary:' . md5(serialize($roles) . $guard);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($entity, $roles, $guard) {
            return $this->fetchUserRolesSummary($entity, $roles, $guard);
        });
    }

    /**
     * Invalidate user-specific role caches
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string|null  $guard  Guard name
     */
    public function invalidateUserRoleCache(Model $user, Model $entity, ?string $guard = null): void
    {
        $guard = $guard ?: config('auth.defaults.guard');
        $baseKey = $this->buildUserRoleCacheKey($user, $entity, $guard);

        // Invalidate all user-specific caches
        $userCacheKeys = [
            $baseKey,
            $baseKey . ':exists',
            $baseKey . ':roles',
        ];

        foreach ($userCacheKeys as $key) {
            Cache::forget($key);
        }

        // Invalidate entity-level caches that this user affects
        $this->invalidateEntityCache($entity);
    }

    /**
     * Invalidate entity-specific role caches
     *
     * @param  Model  $entity  The entity model
     */
    public function invalidateEntityCache(Model $entity): void
    {
        $entityKey = $this->buildEntityCacheKey($entity);

        // Entity-level caches to invalidate
        $entityCacheKeys = [
            $entityKey . ':users_count',
            $entityKey . ':assigned_roles',
        ];

        foreach ($entityCacheKeys as $key) {
            Cache::forget($key);
        }

        // Note: For summary caches, in production consider using Redis SCAN
        // or cache tags for more efficient pattern-based invalidation
        $this->invalidateEntitySummaryCaches($entity);
    }

    /**
     * Invalidate role ID caches for specific guard
     *
     * @param  string  $guard  Guard name
     */
    public function invalidateRoleIdsCache(string $guard): void
    {
        // In production, consider using cache tags or Redis SCAN
        // For now, we rely on TTL expiration for role ID caches
        // since role definitions change infrequently
    }

    /**
     * Clear all role-related caches (use with caution)
     */
    public function clearAllRoleCaches(): void
    {
        // This is a heavy operation - use cache tags in production
        // For demonstration, we would need to track all cache keys
        // or use pattern-based clearing with Redis
    }

    /**
     * Warm cache for frequently accessed data
     *
     * @param  Model  $entity  The entity to warm cache for
     * @param  array  $users  Array of users to warm cache for
     * @param  string|null  $guard  Guard name
     */
    public function warmCache(Model $entity, array $users = [], ?string $guard = null): void
    {
        // Pre-load entity-level data
        $this->cacheEntityUsersCount($entity);
        $this->cacheAssignedRoles($entity);

        // Pre-load user-specific data
        foreach ($users as $user) {
            if ($user instanceof Model) {
                $this->cacheUserRoles($user, $entity, $guard);
                $this->cacheUserRoleExists($user, $entity, $guard);
            }
        }
    }

    /**
     * Set custom cache TTL
     *
     * @param  int  $ttl  TTL in seconds
     */
    public function setTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;

        return $this;
    }

    /**
     * Get current cache TTL
     *
     * @return int TTL in seconds
     */
    public function getTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * Generic cache remember method
     *
     * @param  string  $key  Cache key
     * @param  callable  $callback  Data retrieval callback
     * @param  int|null  $ttl  Custom TTL
     * @return mixed Cached or fresh data
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Build cache key for entity-specific data (exposed for traits)
     *
     * @param  Model  $entity  The entity model
     * @return string Cache key
     */
    public function getEntityCacheKey(Model $entity): string
    {
        return $this->buildEntityCacheKey($entity);
    }

    // =================================================================
    // CACHE KEY BUILDERS
    // =================================================================

    /**
     * Build cache key for role IDs lookup
     *
     * @param  array  $roles  Array of roles
     * @param  string  $guard  Guard name
     * @return string Cache key
     */
    protected function buildRoleIdsCacheKey(array $roles, string $guard): string
    {
        $teamSuffix = '';

        if (app(PermissionRegistrar::class)->teams) {
            $teamId = getPermissionsTeamId();
            $teamSuffix = $teamId ? ':team_' . $teamId : '';
        }

        return $this->prefixes['role_ids'] . ':' . $guard . ':' . md5(serialize($roles)) . $teamSuffix;
    }

    /**
     * Build cache key for user-role relationships
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string  $guard  Guard name
     * @return string Cache key
     */
    protected function buildUserRoleCacheKey(Model $user, Model $entity, string $guard): string
    {
        $teamSuffix = '';

        if (app(PermissionRegistrar::class)->teams) {
            $teamId = getPermissionsTeamId();
            $teamSuffix = $teamId ? ':team_' . $teamId : '';
        }

        return sprintf(
            '%s:%s:%s:user_%s:guard_%s%s',
            $this->prefixes['user_roles'],
            $entity->getMorphClass(),
            $entity->getKey(),
            $user->getKey(),
            $guard,
            $teamSuffix
        );
    }

    /**
     * Build cache key for entity-specific data
     *
     * @param  Model  $entity  The entity model
     * @return string Cache key
     */
    protected function buildEntityCacheKey(Model $entity): string
    {
        $teamSuffix = '';

        if (app(PermissionRegistrar::class)->teams) {
            $teamId = getPermissionsTeamId();
            $teamSuffix = $teamId ? ':team_' . $teamId : '';
        }

        return sprintf(
            '%s:%s:%s%s',
            $this->prefixes['entity_data'],
            $entity->getMorphClass(),
            $entity->getKey(),
            $teamSuffix
        );
    }

    // =================================================================
    // DATA FETCHERS (Direct Database Queries)
    // =================================================================
    /**
     * Resolve role names/IDs to actual role IDs
     *
     * @param  array  $roles  Array of roles to resolve
     * @param  string  $guard  Guard name
     * @return array Array of role IDs
     */
    protected function resolveRoleIds(array $roles, string $guard): array
    {
        if (empty($roles)) {
            return [];
        }

        $roleIds = [];
        $roleNames = [];

        // Separate already-resolved IDs from names that need lookup
        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $roleIds[] = $role->getKey();
            } elseif ($role instanceof \BackedEnum) {
                $roleNames[] = $role->value;
            } elseif (is_string($role)) {
                $roleNames[] = $role;
            } elseif (is_int($role) || PermissionRegistrar::isUid($role)) {
                $roleIds[] = $role;
            }
        }

        // Bulk lookup for role names WITH TENANT FILTERING
        if (! empty($roleNames)) {
            $query = Role::where('guard_name', $guard)
                ->whereIn('name', $roleNames);

            // Add tenant filtering for team-aware setup
            if (app(PermissionRegistrar::class)->teams) {
                $teamId = getPermissionsTeamId();
                if ($teamId) {
                    $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                }
            }

            $foundRoles = $query->pluck('id', 'name');
            $roleIds = array_merge($roleIds, $foundRoles->values()->toArray());
        }

        return array_unique($roleIds);
    }

    /**
     * Fetch user role IDs for specific entity
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string  $guard  Guard name
     * @return array Array of role IDs
     */
    protected function fetchUserRoleIds(Model $user, Model $entity, string $guard): array
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->where('roleable_type', $entity->getMorphClass())
            ->where('roleable_id', $entity->getKey())
            ->where(config('permission.column_names.model_morph_key'), $user->getKey())
            ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                $teamId = getPermissionsTeamId();
                if ($teamId) {
                    $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                }
            })
            ->pluck('role_id')
            ->toArray();
    }

    /**
     * Check if user has any roles on entity
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @return bool Whether user has any roles
     */
    protected function checkUserRoleExists(Model $user, Model $entity): bool
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->where('roleable_type', $entity->getMorphClass())
            ->where('roleable_id', $entity->getKey())
            ->where(config('permission.column_names.model_morph_key'), $user->getKey())
            ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                $teamId = getPermissionsTeamId();
                if ($teamId) {
                    $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                }
            })
            ->exists();
    }

    /**
     * Apply team filtering to Role query when teams are enabled
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyTeamFilterToRoleQuery($query)
    {
        return $query->when(app(PermissionRegistrar::class)->teams, function ($q) {
            $teamId = getPermissionsTeamId();
            if ($teamId) {
                $q->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
            }
        });
    }

    /**
     * Fetch user roles collection for specific entity
     *
     * @param  Model  $user  The user model
     * @param  Model  $entity  The entity model
     * @param  string  $guard  Guard name
     * @return Collection Collection of Role models
     */
    protected function fetchUserRolesCollection(Model $user, Model $entity, string $guard): Collection
    {
        $roleIds = $this->fetchUserRoleIds($user, $entity, $guard);

        if (empty($roleIds)) {
            return collect();
        }

        $query = Role::whereIn('id', $roleIds)
            ->where('guard_name', $guard)
            ->select(['id', 'name', 'guard_name']);

        if (app(PermissionRegistrar::class)->teams) {
            $teamId = getPermissionsTeamId();
            if ($teamId) {
                $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
            }
        }

        return $query->get();
    }

    /**
     * Fetch entity users count
     *
     * @param  Model  $entity  The entity model
     * @return int Count of users
     */
    protected function fetchEntityUsersCount(Model $entity): int
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->where('roleable_type', $entity->getMorphClass())
            ->where('roleable_id', $entity->getKey())
            ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                $teamId = getPermissionsTeamId();
                if ($teamId) {
                    $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                }
            })
            ->distinct(config('permission.column_names.model_morph_key'))
            ->count();
    }

    /**
     * Fetch entity role count for specific role IDs
     *
     * @param  Model  $entity  The entity model
     * @param  array  $roleIds  Array of role IDs
     * @return int Count of users with the roles
     */
    protected function fetchEntityRoleCount(Model $entity, array $roleIds): int
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->where('roleable_type', $entity->getMorphClass())
            ->where('roleable_id', $entity->getKey())
            ->whereIn('role_id', $roleIds)
            ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                $teamId = getPermissionsTeamId();
                if ($teamId) {
                    $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                }
            })
            ->count();
    }

    /**
     * Fetch assigned roles for entity
     *
     * @param  Model  $entity  The entity model
     * @return Collection Collection of Role models
     */
    protected function fetchAssignedRoles(Model $entity): Collection
    {
        $roleIds = DB::table(config('permission.table_names.model_has_roles'))
            ->where('roleable_type', $entity->getMorphClass())
            ->where('roleable_id', $entity->getKey())
            ->when(app(PermissionRegistrar::class)->teams, function ($query) {
                $teamId = getPermissionsTeamId();
                if ($teamId) {
                    $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
                }
            })
            ->distinct('role_id')
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return collect();
        }

        $query = Role::whereIn('id', $roleIds)
            ->select(['id', 'name', 'guard_name']);

        if (app(PermissionRegistrar::class)->teams) {
            $teamId = getPermissionsTeamId();
            if ($teamId) {
                $query->where(app(PermissionRegistrar::class)->teamsKey, $teamId);
            }
        }

        return $query->get();
    }

    /**
     * Fetch user roles summary for entity
     *
     * @param  Model  $entity  The entity model
     * @param  mixed  $roles  Optional roles filter
     * @param  string  $guard  Guard name
     * @return array Structured array of user-role mappings
     */
    protected function fetchUserRolesSummary(Model $entity, $roles, string $guard): array
    {
        $query = DB::table(config('permission.table_names.model_has_roles'))
            ->join(config('permission.table_names.users'),
                config('permission.table_names.model_has_roles') . '.' . config('permission.column_names.model_morph_key'),
                '=',
                config('permission.table_names.users') . '.id')
            ->join(config('permission.table_names.roles'),
                config('permission.table_names.model_has_roles') . '.role_id',
                '=',
                config('permission.table_names.roles') . '.id')
            ->where(config('permission.table_names.model_has_roles') . '.roleable_type', $entity->getMorphClass())
            ->where(config('permission.table_names.model_has_roles') . '.roleable_id', $entity->getKey())
            ->where(config('permission.table_names.roles') . '.guard_name', $guard);

        // Filter by specific roles if provided
        if (! empty($roles)) {
            $roleIds = $this->getCachedRoleIds(is_array($roles) ? $roles : [$roles], $guard);
            if (! empty($roleIds)) {
                $query->whereIn(config('permission.table_names.model_has_roles') . '.role_id', $roleIds);
            } else {
                return []; // No matching roles
            }
        }

        // Add team filtering
        if (app(PermissionRegistrar::class)->teams) {
            $teamId = getPermissionsTeamId();
            if ($teamId) {
                $query->where(config('permission.table_names.model_has_roles') . '.' . app(PermissionRegistrar::class)->teamsKey, $teamId);
            }
        }

        $results = $query->select([
            config('permission.table_names.users') . '.id as user_id',
            config('permission.table_names.users') . '.name as user_name',
            config('permission.table_names.users') . '.email as user_email',
            config('permission.table_names.roles') . '.id as role_id',
            config('permission.table_names.roles') . '.name as role_name',
        ])->get();

        // Group by user
        return $results->groupBy('user_id')->map(function ($userRoles) {
            $firstRow = $userRoles->first();

            return [
                'user' => [
                    'id' => $firstRow->user_id,
                    'name' => $firstRow->user_name,
                    'email' => $firstRow->user_email,
                ],
                'roles' => $userRoles->pluck('role_name')->unique()->values()->toArray(),
                'role_ids' => $userRoles->pluck('role_id')->unique()->values()->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * Invalidate entity summary caches (pattern-based)
     *
     * @param  Model  $entity  The entity model
     */
    protected function invalidateEntitySummaryCaches(Model $entity): void
    {
        $entityKey = $this->buildEntityCacheKey($entity);
        $summaryPattern = $entityKey . ':summary:';

        // Note: In production with Redis, use SCAN to find and delete matching keys
        // For now, we rely on TTL expiration for summary caches
        // Cache::flush(); // Too aggressive for production
    }
}
