<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Notifications\Participant\AssignedToEntity;
use Hdaklue\MargRbac\Events\Role\EntityRoleAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class SendAssignedToEntityNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(EntityRoleAssigned $event): void
    {
        $roleLabel = is_string($event->role)
            ? $event->role
            : $event->role->getLabel();

        $event->user->notify(new AssignedToEntity($event->entity, $roleLabel));
    }
}
