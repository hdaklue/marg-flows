<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Contracts\Role\AssignableEntity;
use App\Contracts\Role\RoleableEntity;
use App\Notifications\Participant\RemovedFromEntity;
use Lorisleiva\Actions\Concerns\AsAction;

final class RemoveParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user)
    {
        $roleable->removeParticipant($user);
        $user->notify(new RemovedFromEntity($roleable));
    }
}
