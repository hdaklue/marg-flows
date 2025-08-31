<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Tenant\MemberRemoved;
use App\Events\Tenant\TanantMemberAdded;
use App\Events\Tenant\TenantCreated;
use Hdaklue\MargRbac\Events\Role\EntityRoleAssigned;
use Hdaklue\MargRbac\Events\Role\EntityRoleRemoved;
use Hdaklue\MargRbac\Events\Tenant\TenantCreated as PackageTenantCreated;
use Hdaklue\MargRbac\Events\Tenant\TanantMemberAdded as PackageTanantMemberAdded;
use Hdaklue\MargRbac\Events\Tenant\TenantMemberRemoved as PackageTenantMemberRemoved;
use Hdaklue\MargRbac\Events\TenantMemberInvited;
use Hdaklue\MargRbac\Events\TenantSwitched;
use Hdaklue\MargRbac\Facades\RoleManager;
use App\Models\User;
use App\Notifications\Participant\AssignedToEntity;
use App\Notifications\Participant\RemovedFromEntity;
use App\Notifications\TenantCreatedNotification;
use Illuminate\Events\Dispatcher;

final class TenantEventSubscriber
{
    /**
     * Handle the Created event.
     */
    public function handleTenantCreated(TenantCreated $event): void
    {

        $users = User::appAdmin()
            ->where('id', '!=', $event->user->id)
            ->get();

        foreach ($users as $user) {
            $user->notify(new TenantCreatedNotification($event->tenant));
        }

    }

    public function handleMemberRemoved(MemberRemoved $event): void
    {
        RoleManager::clearCache($event->tenant);
        logger("cache should be cleared for {$event->tenant->id}");
        $event->memberRemoved->notify(new RemovedFromEntity($event->tenant));
    }

    public function handleMemberAdded(TanantMemberAdded $event): void
    {

        RoleManager::clearCache($event->tenant);
        $event->user->notify(new AssignedToEntity($event->tenant, $event->role->getLabel()));

    }

    /**
     * Handle package tenant created event
     */
    public function handlePackageTenantCreated(PackageTenantCreated $event): void
    {
        $users = User::appAdmin()
            ->where('id', '!=', $event->user->id)
            ->get();

        foreach ($users as $user) {
            $user->notify(new TenantCreatedNotification($event->tenant));
        }
    }

    /**
     * Handle package member added event
     */
    public function handlePackageMemberAdded(PackageTanantMemberAdded $event): void
    {
        RoleManager::clearCache($event->tenant);
        // Handle any app-specific logic for member addition
    }

    /**
     * Handle package member removed event
     */
    public function handlePackageMemberRemoved(PackageTenantMemberRemoved $event): void
    {
        RoleManager::clearCache($event->tenant);
        $event->memberRemoved->notify(new RemovedFromEntity($event->tenant));
    }

    /**
     * Handle package tenant member invited event
     */
    public function handleTenantMemberInvited(TenantMemberInvited $event): void
    {
        // Handle any app-specific logic for member invitation
        // The package already handles the notification
    }

    /**
     * Handle package tenant switched event
     */
    public function handleTenantSwitched(TenantSwitched $event): void
    {
        // Handle any app-specific logic for tenant switching
        // Clear caches, logs, etc.
    }

    /**
     * Handle package entity role assigned event
     */
    public function handleEntityRoleAssigned(EntityRoleAssigned $event): void
    {
        // Handle any app-specific logic when roles are assigned
        // This could be for flows, tenants, etc.
    }

    /**
     * Handle package entity role removed event
     */
    public function handleEntityRoleRemoved(EntityRoleRemoved $event): void
    {
        // Handle any app-specific logic when roles are removed
        // This could be for flows, tenants, etc.
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            // App events
            TenantCreated::class => 'handleTenantCreated',
            MemberRemoved::class => 'handleMemberRemoved',
            TanantMemberAdded::class => 'handleMemberAdded',
            
            // Package events
            PackageTenantCreated::class => 'handlePackageTenantCreated',
            PackageTanantMemberAdded::class => 'handlePackageMemberAdded',
            PackageTenantMemberRemoved::class => 'handlePackageMemberRemoved',
            TenantMemberInvited::class => 'handleTenantMemberInvited',
            TenantSwitched::class => 'handleTenantSwitched',
            EntityRoleAssigned::class => 'handleEntityRoleAssigned',
            EntityRoleRemoved::class => 'handleEntityRoleRemoved',
        ];
    }
}
