<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Role\EntityAllRolesRemoved;
use App\Events\Role\EntityBulkRolesUpdated;
use App\Events\Role\EntityRoleAssigned;
use App\Events\Role\EntityRoleRemoved;
use App\Events\Role\EntityRolesSynced;
use App\Listeners\Role\RoleEventListener;
use App\Services\Role\RoleCacheService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Role Service Provider
 *
 * Registers the role caching service, configures event listeners,
 * and sets up the entity-aware role system components.
 *
 * ## Registered Services:
 * - `RoleCacheService`: Singleton for centralized role caching
 * - Event listeners for all entity role events
 * - Configuration validation and setup
 *
 * ## Configuration:
 * Add to config/app.php providers array:
 * ```php
 * 'providers' => [
 *     // ... other providers
 *     App\Providers\RoleServiceProvider::class,
 * ],
 * ```
 *
 * ## Configuration Options:
 * Add to config/permission.php:
 * ```php
 * 'role_caching' => [
 *     'enabled' => true,
 *     'default_ttl' => 300, // 5 minutes
 *     'cache_driver' => null, // Use default cache driver
 * ],
 * 'events_enabled' => true,
 * 'audit_logging' => true,
 * 'performance_monitoring' => false,
 * 'cache_warming' => true,
 * ```
 *
 * @author Hassan Ibrahim
 *
 * @version 1.0.0
 *
 * @since 2025-06-16
 */
class RoleCacheServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [
        RoleCacheService::class => RoleCacheService::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register RoleCacheService as singleton
        $this->app->singleton(function ($app): RoleCacheService {
            $service = new RoleCacheService;

            // Configure TTL from config
            $ttl = config('permission.role_caching.default_ttl', 300);
            $service->setTtl($ttl);

            return $service;
        });

        // Merge default configuration
        $this->mergeConfigFrom(
            $this->getConfigPath(),
            'permission',
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Validate configuration
        $this->validateConfiguration();

        // Register event listeners if events are enabled
        if (config('permission.events_enabled', true)) {
            $this->registerEventListeners();
        }

        // Publish configuration if running in console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->getConfigPath() => config_path('permission-role-cache.php'),
            ], 'config');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            RoleCacheService::class,
        ];
    }

    /**
     * Register event listeners for role events
     */
    protected function registerEventListeners(): void
    {
        // Register the event subscriber
        Event::subscribe(RoleEventListener::class);

        // Alternative: Register individual listeners
        // Event::listen(EntityRoleAssigned::class, [RoleEventListener::class, 'handleRoleAssigned']);
        // Event::listen(EntityRoleRemoved::class, [RoleEventListener::class, 'handleRoleRemoved']);
        // Event::listen(EntityAllRolesRemoved::class, [RoleEventListener::class, 'handleAllRolesRemoved']);
        // Event::listen(EntityRolesSynced::class, [RoleEventListener::class, 'handleRolesSynced']);
        // Event::listen(EntityBulkRolesUpdated::class, [RoleEventListener::class, 'handleBulkRolesUpdated']);
    }

    /**
     * Validate the configuration
     */
    protected function validateConfiguration(): void
    {
        // Check if Spatie Permission is configured
        throw_unless(config('permission.table_names'), new \RuntimeException(
            'Spatie Permission package is not properly configured. ' .
            'Please run: php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"',
        ));

        // Validate role caching configuration
        $roleCachingConfig = config('permission.role_caching', []);

        if (isset($roleCachingConfig['enabled']) && $roleCachingConfig['enabled']) {
            // Check if cache is configured
            $cacheDriver = $roleCachingConfig['cache_driver'] ?? config('cache.default');
            $cacheConfig = config("cache.stores.{$cacheDriver}");

            throw_unless($cacheConfig, new \RuntimeException(
                "Cache driver '{$cacheDriver}' is not configured. " .
                'Please configure a cache driver for role caching.',
            ));
        }

        // Validate TTL
        $ttl = config('permission.role_caching.default_ttl', 300);
        throw_if(! is_int($ttl) || $ttl < 0, new \InvalidArgumentException(
            'role_caching.default_ttl must be a positive integer representing seconds.',
        ));
    }

    /**
     * Get the config file path
     */
    protected function getConfigPath(): string
    {
        return __DIR__ . '/../../config/permission-role-cache.php';
    }
}
