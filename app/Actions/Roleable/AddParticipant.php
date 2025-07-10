<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Contracts\Role\AssignableEntity;
use App\Contracts\Role\HasParticipants;
use App\Enums\Role\RoleEnum;
use App\Notifications\Participant\AssignedToEntity;
use BackedEnum;
use Lorisleiva\Actions\Concerns\AsAction;

final class AddParticipant
{
    use AsAction;

    public function handle(HasParticipants $roleable, AssignableEntity $user, RoleEnum|string $role)
    {

        if ($role instanceof BackedEnum) {
            $role = $role->value;
        }
        $roleable->addParticipant($user, $role);

        $user->notify(new AssignedToEntity($roleable, RoleEnum::from($role)->getLabel()));
    }

    public function asJob(HasParticipants $roleable, AssignableEntity $user, RoleEnum|string $role)
    {
        $this->handle($roleable, $user, $role);
    }
}
