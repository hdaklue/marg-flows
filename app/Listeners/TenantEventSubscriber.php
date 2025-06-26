<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Tenant\MemberRemoved;
use App\Events\Tenant\TanantMemberAdded;
use App\Events\Tenant\TenantCreated;
use App\Models\User;
use App\Notifications\Participant\AsignedToTenant;
use App\Notifications\Participant\RemovedFromTenant;
use App\Notifications\TenantCreatedNotification;
use Illuminate\Events\Dispatcher;

class TenantEventSubscriber
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
        $event->memberRemoved->notify(new RemovedFromTenant($event->tenant->name));
    }

    public function handleMemberAdded(TanantMemberAdded $event): void
    {

        $event->user->notify(new AsignedToTenant($event->tenant->name, $event->role->getLabel()));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            TenantCreated::class => 'handleTenantCreated',
            MemberRemoved::class => 'handleMemberRemoved',
            TanantMemberAdded::class => 'handleMemberAdded',

        ];
    }
}
