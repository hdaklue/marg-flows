<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Contracts\Roles\Roleable;
use App\Models\User;
use App\Notifications\Participant\RemovedFromEntity;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveParticipant
{
    use AsAction;

    public function handle(Roleable $roleable, User $user)
    {
        $roleable->removeAllUserRoles($user);
        $user->notify(new RemovedFromEntity($roleable));
    }
}
