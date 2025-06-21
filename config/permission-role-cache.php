<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Entity-Aware Role Caching Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the entity-aware role
    | caching system, events, and performance monitoring features.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Role Caching Settings
    |--------------------------------------------------------------------------
    |
    | Configure the caching behavior for role-related queries. The caching
    | system dramatically improves performance by storing frequently accessed
    | role data and user-role relationships in memory.
    |
    */

    'role_caching' => [
        /*
         * Enable or disable role caching entirely.
         * When disabled, all queries will hit the database directly.
         */
        'enabled' => env('ROLE_CACHING_ENABLED', true),

        /*
         * Default cache TTL (Time To Live) in seconds.
         * This is the default expiration time for cached role data.
         *
         * Recommended values:
         * - Development: 60 (1 minute)
         * - Production: 300 (5 minutes)
         * - High-frequency changes: 30 (30 seconds)
         */
        'default_ttl' => (int) env('ROLE_CACHING_TTL', 300),

        /*
         * Cache driver to use for role caching.
         * Falls back to CACHE_DRIVER, then Laravel's cache.default.
         *
         * Recommended drivers:
         * - Redis: Best performance, supports cache tags
         * - Memcached: Good performance, no cache tags
         * - Database: Persistent but slower
         * - Array: In-memory only, for testing
         */
        'cache_driver' => env('ROLE_CACHING_DRIVER', env('CACHE_DRIVER', null)),

        /*
         * Cache key prefix for all role-related cache entries.
         * Useful for cache namespacing in multi-tenant applications.
         */
        'key_prefix' => env('ROLE_CACHING_PREFIX', 'entity_roles'),

        /*
         * Enable cache tags support (requires Redis or compatible driver).
         * Cache tags allow for more efficient cache invalidation patterns.
         */
        'use_cache_tags' => env('ROLE_CACHING_TAGS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event System Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the role event system that fires events for all role changes.
    | This enables audit logging, cache warming, and custom business logic.
    |
    */

    /*
     * Enable or disable role events entirely.
     * When disabled, no events will be fired for role changes.
     */
    'events_enabled' => env('ROLE_EVENTS_ENABLED', true),

    /*
     * Enable audit logging of all role changes.
     * Logs are written using Laravel's logging system.
     */
    'audit_logging' => env('ROLE_AUDIT_LOGGING', true),

    /*
     * Log channel to use for role audit logs.
     * If null, uses the default log channel.
     */
    'audit_log_channel' => env('ROLE_AUDIT_LOG_CHANNEL', null),

    /*
     * Enable performance monitoring for role operations.
     * Logs execution time and memory usage for debugging.
     */
    'performance_monitoring' => env('ROLE_PERFORMANCE_MONITORING', false),

    /*
     * Enable automatic cache warming after role changes.
     * Pre-loads related data to improve subsequent query performance.
     */
    'cache_warming' => env('ROLE_CACHE_WARMING', true),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Additional database settings specific to the entity-aware role system.
    |
    */

    'database' => [
        /*
         * Enable or disable database query optimization features.
         * Includes index hints, query plan optimization, etc.
         */
        'optimize_queries' => env('ROLE_DB_OPTIMIZE', true),

        /*
         * Maximum number of role IDs to include in a single IN() query.
         * Prevents query plan issues with very large role sets.
         */
        'max_in_query_size' => (int) env('ROLE_MAX_IN_QUERY', 1000),

        /*
         * Enable query result caching at the database level.
         * Requires database driver support (MySQL query cache, etc.).
         */
        'enable_query_cache' => env('ROLE_DB_QUERY_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Validation
    |--------------------------------------------------------------------------
    |
    | Security-related settings for the role system.
    |
    */

    'security' => [
        /*
         * Enable strict validation of role assignments.
         * Validates that roles exist and are appropriate for the entity type.
         */
        'strict_validation' => env('ROLE_STRICT_VALIDATION', true),

        /*
         * Enable rate limiting for role assignment operations.
         * Helps prevent abuse of role assignment endpoints.
         */
        'rate_limiting' => env('ROLE_RATE_LIMITING', false),

        /*
         * Maximum number of role assignments per minute per user.
         * Only applies when rate_limiting is enabled.
         */
        'rate_limit_per_minute' => (int) env('ROLE_RATE_LIMIT', 60),

        /*
         * Enable logging of failed role operations for security monitoring.
         */
        'log_failures' => env('ROLE_LOG_FAILURES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Support
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-tenant applications using the role system.
    |
    */

    'multi_tenancy' => [
        /*
         * Enable tenant-aware caching.
         * Includes tenant ID in cache keys to prevent cross-tenant data leaks.
         */
        'tenant_aware_caching' => env('ROLE_TENANT_CACHING', true),

        /*
         * Tenant ID resolver callback.
         * Function name or callable to resolve current tenant ID.
         */
        'tenant_resolver' => env('ROLE_TENANT_RESOLVER', 'getPermissionsTeamId'),

        /*
         * Enable automatic tenant validation for role operations.
         * Ensures roles can only be assigned within the correct tenant scope.
         */
        'validate_tenant_scope' => env('ROLE_VALIDATE_TENANT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Debugging
    |--------------------------------------------------------------------------
    |
    | Settings useful during development and debugging.
    |
    */

    'debug' => [
        /*
         * Enable debug mode for role operations.
         * Adds extra logging and validation checks.
         */
        'enabled' => env('ROLE_DEBUG', env('APP_DEBUG', false)),

        /*
         * Log all cache hits and misses for analysis.
         * Useful for optimizing cache strategies.
         */
        'log_cache_operations' => env('ROLE_DEBUG_CACHE', false),

        /*
         * Log all database queries generated by the role system.
         * Useful for performance analysis and optimization.
         */
        'log_queries' => env('ROLE_DEBUG_QUERIES', false),

        /*
         * Enable query count tracking to detect N+1 problems.
         */
        'track_query_count' => env('ROLE_DEBUG_QUERY_COUNT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Event Handlers
    |--------------------------------------------------------------------------
    |
    | Register custom event handlers for role events. These will be called
    | in addition to the default RoleEventListener.
    |
    */

    'custom_listeners' => [
        /*
         * Custom listeners for EntityRoleAssigned event.
         * Array of class names or callables.
         */
        'role_assigned' => [
            // 'App\Listeners\CustomRoleAssignedListener',
        ],

        /*
         * Custom listeners for EntityRoleRemoved event.
         */
        'role_removed' => [
            // 'App\Listeners\CustomRoleRemovedListener',
        ],

        /*
         * Custom listeners for EntityAllRolesRemoved event.
         */
        'all_roles_removed' => [
            // 'App\Listeners\CustomAllRolesRemovedListener',
        ],

        /*
         * Custom listeners for EntityRolesSynced event.
         */
        'roles_synced' => [
            // 'App\Listeners\CustomRolesSyncedListener',
        ],

        /*
         * Custom listeners for EntityBulkRolesUpdated event.
         */
        'bulk_roles_updated' => [
            // 'App\Listeners\CustomBulkRolesUpdatedListener',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with other packages and services.
    |
    */

    'integrations' => [
        /*
         * Enable integration with Laravel Telescope for query monitoring.
         */
        'telescope' => env('ROLE_TELESCOPE_INTEGRATION', false),

        /*
         * Enable integration with Laravel Horizon for event queue monitoring.
         */
        'horizon' => env('ROLE_HORIZON_INTEGRATION', false),

        /*
         * Enable integration with Spatie Laravel Permission events.
         * Fires original Spatie events alongside custom events.
         */
        'spatie_events' => env('ROLE_SPATIE_EVENTS', false),

        /*
         * Enable metrics collection for external monitoring systems.
         */
        'metrics_collection' => env('ROLE_METRICS_COLLECTION', false),
    ],
];
