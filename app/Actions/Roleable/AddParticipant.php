<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Contracts\Role\AssignableEntity;
use App\Contracts\Role\RoleableEntity;
use App\Enums\Role\RoleEnum;
use App\Notifications\Participant\AssignedToEntity;
use Lorisleiva\Actions\Concerns\AsAction;

final class AddParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user, RoleEnum|string $role)
    {

        if ($role instanceof RoleEnum) {
            $role = $role->value;
        }
        $roleable->addParticipant($user, $role);

        $user->notify(new AssignedToEntity($roleable, RoleEnum::from($role)->getLabel()));
    }

    public function asJob(RoleableEntity $roleable, AssignableEntity $user, RoleEnum|string $role)
    {
        $this->handle($roleable, $user, $role);
    }
}
