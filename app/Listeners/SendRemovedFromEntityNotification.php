<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Notifications\Participant\RemovedFromEntity;
use Hdaklue\MargRbac\Events\Role\EntityAllRolesRemoved;
use Hdaklue\MargRbac\Events\Role\EntityRoleRemoved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class SendRemovedFromEntityNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the EntityRoleRemoved event.
     */
    public function handle(EntityRoleRemoved|EntityAllRolesRemoved $event): void
    {
        $event->user->notify(new RemovedFromEntity($event->entity));
    }
}
