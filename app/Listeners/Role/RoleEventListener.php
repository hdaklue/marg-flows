<?php

namespace App\Listeners\Role;

use App\Events\Role\EntityAllRolesRemoved;
use App\Events\Role\EntityBulkRolesUpdated;
use App\Events\Role\EntityRoleAssigned;
use App\Events\Role\EntityRoleRemoved;
use App\Events\Role\EntityRolesSynced;
use App\Services\Role\RoleCacheService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Role Event Listener
 *
 * Handles all entity-aware role events with cache management, logging,
 * and optional additional business logic. Provides a centralized place
 * to respond to role changes across the application.
 *
 * ## Features:
 * - **Cache Warming**: Pre-loads related data after role changes
 * - **Audit Logging**: Logs all role changes for security/compliance
 * - **Notification Hooks**: Triggers for external notifications
 * - **Performance Monitoring**: Tracks role operation metrics
 * - **Business Logic**: Custom hooks for application-specific needs
 *
 * ## Usage:
 * Register in EventServiceProvider:
 * ```php
 * protected $listen = [
 *     EntityRoleAssigned::class => [
 *         RoleEventListener::class . '@handleRoleAssigned',
 *     ],
 *     EntityRoleRemoved::class => [
 *         RoleEventListener::class . '@handleRoleRemoved',
 *     ],
 *     // ... other events
 * ];
 * ```
 *
 * Or use the subscribe method for all events:
 * ```php
 * protected $subscribe = [
 *     RoleEventListener::class,
 * ];
 * ```
 *
 * @author Hassan Ibrahim
 *
 * @version 1.0.0
 *
 * @since 2025-06-16
 */
class RoleEventListener
{
    /**
     * Role caching service instance
     */
    protected RoleCacheService $RoleCacheService;

    /**
     * Whether to enable audit logging
     */
    protected bool $auditLogging;

    /**
     * Whether to enable performance monitoring
     */
    protected bool $performanceMonitoring;

    /**
     * Whether to enable cache warming
     */
    protected bool $cacheWarming;

    public function __construct(RoleCacheService $RoleCacheService)
    {
        $this->RoleCacheService = $RoleCacheService;
        $this->auditLogging = config('permission.audit_logging', true);
        $this->performanceMonitoring = config('permission.performance_monitoring', false);
        $this->cacheWarming = config('permission.cache_warming', true);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            EntityRoleAssigned::class,
            [static::class, 'handleRoleAssigned']
        );

        $events->listen(
            EntityRoleRemoved::class,
            [static::class, 'handleRoleRemoved']
        );

        $events->listen(
            EntityAllRolesRemoved::class,
            [static::class, 'handleAllRolesRemoved']
        );

        $events->listen(
            EntityRolesSynced::class,
            [static::class, 'handleRolesSynced']
        );

        $events->listen(
            EntityBulkRolesUpdated::class,
            [static::class, 'handleBulkRolesUpdated']
        );
    }

    /**
     * Handle role assigned event
     */
    public function handleRoleAssigned(EntityRoleAssigned $event): void
    {
        $startTime = $this->startPerformanceMonitoring();

        try {
            // Audit logging
            $this->logRoleChange('assigned', $event->user, $event->entity, $event->role, $event->guard);

            // Cache warming - pre-load related data
            if ($this->cacheWarming) {
                $this->warmRelatedCaches($event->user, $event->entity, $event->guard);
            }

            // Custom business logic hook
            $this->onRoleAssigned($event);

        } catch (\Exception $e) {
            Log::error('Error handling EntityRoleAssigned event', [
                'user_id' => $event->user->getKey(),
                'entity_type' => $event->entity->getMorphClass(),
                'entity_id' => $event->entity->getKey(),
                'role' => $event->role,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->endPerformanceMonitoring('role_assigned', $startTime);
        }
    }

    /**
     * Handle role removed event
     */
    public function handleRoleRemoved(EntityRoleRemoved $event): void
    {
        $startTime = $this->startPerformanceMonitoring();

        try {
            // Audit logging
            $this->logRoleChange('removed', $event->user, $event->entity, $event->role, $event->guard);

            // Cache warming - ensure remaining roles are cached
            if ($this->cacheWarming) {
                $this->warmRelatedCaches($event->user, $event->entity, $event->guard);
            }

            // Custom business logic hook
            $this->onRoleRemoved($event);

        } catch (\Exception $e) {
            Log::error('Error handling EntityRoleRemoved event', [
                'user_id' => $event->user->getKey(),
                'entity_type' => $event->entity->getMorphClass(),
                'entity_id' => $event->entity->getKey(),
                'role' => $event->role,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->endPerformanceMonitoring('role_removed', $startTime);
        }
    }

    /**
     * Handle all roles removed event
     */
    public function handleAllRolesRemoved(EntityAllRolesRemoved $event): void
    {
        $startTime = $this->startPerformanceMonitoring();

        try {
            // Audit logging
            $this->logRoleChange('all_removed', $event->user, $event->entity, 'ALL', $event->guard);

            // Cache warming - update entity statistics
            if ($this->cacheWarming) {
                $this->warmEntityCaches($event->entity);
            }

            // Custom business logic hook
            $this->onAllRolesRemoved($event);

        } catch (\Exception $e) {
            Log::error('Error handling EntityAllRolesRemoved event', [
                'user_id' => $event->user->getKey(),
                'entity_type' => $event->entity->getMorphClass(),
                'entity_id' => $event->entity->getKey(),
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->endPerformanceMonitoring('all_roles_removed', $startTime);
        }
    }

    /**
     * Handle roles synced event
     */
    public function handleRolesSynced(EntityRolesSynced $event): void
    {
        $startTime = $this->startPerformanceMonitoring();

        try {
            // Audit logging
            $this->logRoleSync($event->user, $event->entity, $event->oldRoles, $event->newRoles, $event->guard);

            // Cache warming - pre-load new role data
            if ($this->cacheWarming) {
                $this->warmRelatedCaches($event->user, $event->entity, $event->guard);
                $this->warmEntityCaches($event->entity);
            }

            // Custom business logic hook
            $this->onRolesSynced($event);

        } catch (\Exception $e) {
            Log::error('Error handling EntityRolesSynced event', [
                'user_id' => $event->user->getKey(),
                'entity_type' => $event->entity->getMorphClass(),
                'entity_id' => $event->entity->getKey(),
                'old_roles' => $event->oldRoles,
                'new_roles' => $event->newRoles,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->endPerformanceMonitoring('roles_synced', $startTime);
        }
    }

    /**
     * Handle bulk roles updated event
     */
    public function handleBulkRolesUpdated(EntityBulkRolesUpdated $event): void
    {
        $startTime = $this->startPerformanceMonitoring();

        try {
            // Audit logging
            $this->logBulkUpdate($event->entity, $event->userRoleMap, $event->guard);

            // Cache warming - pre-load data for all affected users
            if ($this->cacheWarming) {
                $users = array_keys($event->userRoleMap);
                $this->RoleCacheService->warmCache($event->entity, $users, $event->guard);
            }

            // Custom business logic hook
            $this->onBulkRolesUpdated($event);

        } catch (\Exception $e) {
            Log::error('Error handling EntityBulkRolesUpdated event', [
                'entity_type' => $event->entity->getMorphClass(),
                'entity_id' => $event->entity->getKey(),
                'user_count' => count($event->userRoleMap),
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->endPerformanceMonitoring('bulk_roles_updated', $startTime);
        }
    }

    // =================================================================
    // CACHE WARMING HELPERS
    // =================================================================

    /**
     * Warm caches for user and entity relationship
     */
    protected function warmRelatedCaches($user, $entity, ?string $guard = null): void
    {
        // Pre-load commonly accessed data
        $this->RoleCacheService->cacheUserRoles($user, $entity, $guard);
        $this->RoleCacheService->cacheUserRoleExists($user, $entity, $guard);
        $this->RoleCacheService->cacheUserRolesCollection($user, $entity, $guard);
    }

    /**
     * Warm caches for entity-level data
     */
    protected function warmEntityCaches($entity): void
    {
        // Pre-load entity statistics
        $this->RoleCacheService->cacheEntityUsersCount($entity);
        $this->RoleCacheService->cacheAssignedRoles($entity);
    }

    // =================================================================
    // AUDIT LOGGING HELPERS
    // =================================================================

    /**
     * Log role change for audit trail
     */
    protected function logRoleChange(string $action, $user, $entity, $role, ?string $guard = null): void
    {
        if (! $this->auditLogging) {
            return;
        }

        Log::info('Entity role change', [
            'action' => $action,
            'user_id' => $user->getKey(),
            'user_email' => $user->email ?? null,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'entity_name' => $entity->name ?? $entity->title ?? null,
            'role' => $role,
            'guard' => $guard,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Log role sync operation
     */
    protected function logRoleSync($user, $entity, array $oldRoles, array $newRoles, ?string $guard = null): void
    {
        if (! $this->auditLogging) {
            return;
        }

        $added = array_diff($newRoles, $oldRoles);
        $removed = array_diff($oldRoles, $newRoles);

        Log::info('Entity roles synced', [
            'action' => 'synced',
            'user_id' => $user->getKey(),
            'user_email' => $user->email ?? null,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'entity_name' => $entity->name ?? $entity->title ?? null,
            'old_roles' => $oldRoles,
            'new_roles' => $newRoles,
            'added_roles' => $added,
            'removed_roles' => $removed,
            'guard' => $guard,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Log bulk update operation
     */
    protected function logBulkUpdate($entity, array $userRoleMap, ?string $guard = null): void
    {
        if (! $this->auditLogging) {
            return;
        }

        $userCount = count($userRoleMap);
        $totalRoleAssignments = array_sum(array_map('count', $userRoleMap));

        Log::info('Entity bulk roles updated', [
            'action' => 'bulk_updated',
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'entity_name' => $entity->name ?? $entity->title ?? null,
            'user_count' => $userCount,
            'total_role_assignments' => $totalRoleAssignments,
            'guard' => $guard,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    // =================================================================
    // PERFORMANCE MONITORING HELPERS
    // =================================================================

    /**
     * Start performance monitoring
     */
    protected function startPerformanceMonitoring(): ?float
    {
        return $this->performanceMonitoring ? microtime(true) : null;
    }

    /**
     * End performance monitoring and log metrics
     */
    protected function endPerformanceMonitoring(string $operation, ?float $startTime): void
    {
        if (! $this->performanceMonitoring || ! $startTime) {
            return;
        }

        $duration = microtime(true) - $startTime;

        Log::debug('Role operation performance', [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);
    }

    // =================================================================
    // CUSTOM BUSINESS LOGIC HOOKS
    // =================================================================

    /**
     * Custom hook for role assigned - override in your application
     */
    protected function onRoleAssigned(EntityRoleAssigned $event): void
    {
        // Example: Send notification to user
        // $this->sendRoleAssignedNotification($event->user, $event->entity, $event->role);

        // Example: Update user's last activity
        // $event->user->touch();

        // Example: Trigger workflow
        // $this->triggerWorkflow('role_assigned', $event);
    }

    /**
     * Custom hook for role removed - override in your application
     */
    protected function onRoleRemoved(EntityRoleRemoved $event): void
    {

        // Example: Check if user still has access
        // $this->checkUserAccess($event->user, $event->entity);

        // Example: Send notification
        // $this->sendRoleRemovedNotification($event->user, $event->entity, $event->role);
    }

    /**
     * Custom hook for all roles removed - override in your application
     */
    protected function onAllRolesRemoved(EntityAllRolesRemoved $event): void
    {
        // Example: Remove user from entity-specific groups
        // $this->removeUserFromEntityGroups($event->user, $event->entity);

        // Example: Send access revoked notification
        // $this->sendAccessRevokedNotification($event->user, $event->entity);
    }

    /**
     * Custom hook for roles synced - override in your application
     */
    protected function onRolesSynced(EntityRolesSynced $event): void
    {
        // Example: Update user's permissions cache
        // $this->updateUserPermissionsCache($event->user, $event->entity);

        // Example: Send role change summary
        // $this->sendRoleChangeSummary($event);
    }

    /**
     * Custom hook for bulk roles updated - override in your application
     */
    protected function onBulkRolesUpdated(EntityBulkRolesUpdated $event): void
    {
        // Example: Send admin notification about bulk changes
        // $this->sendBulkUpdateNotification($event->entity, count($event->userRoleMap));

        // Example: Update entity statistics
        // $this->updateEntityStatistics($event->entity);
    }
}
