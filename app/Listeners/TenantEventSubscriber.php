<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Tenant\TenantCreated;
use App\Models\User;
use App\Notifications\TenantCreatedNotification;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TenantEventSubscriber
{
    /**
     * Handle the Created event.
     */
    public function handleTenantCreated(TenantCreated $event): void
    {
        Log::info('Ran On' . now());
        $users = User::appAdmin()
            ->where('id', '!=', $event->user->id)
            ->get();

        foreach ($users as $user) {

            $user->notify(new TenantCreatedNotification($event->tenant));
        }

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
        ];
    }
}
