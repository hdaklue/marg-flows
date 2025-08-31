<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Notifications\Participant\AssignedToEntity;
use Hdaklue\MargRbac\Actions\Roleable\AddParticipant as PackageAddParticipant;
use Hdaklue\MargRbac\Contracts\Role\AssignableEntity;
use Hdaklue\MargRbac\Contracts\Role\RoleableEntity;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use Lorisleiva\Actions\Concerns\AsAction;

final class AddParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user, RoleEnum|string $role)
    {
        // Call the package action to handle the core functionality
        PackageAddParticipant::run($roleable, $user, $role);

        // Add app-specific logic after assignment
        $roleLabel = $role instanceof RoleEnum ? $role->getLabel() : RoleEnum::from($role)->getLabel();
        $user->notify(new AssignedToEntity($roleable, $roleLabel));
    }

    public function asJob(RoleableEntity $roleable, AssignableEntity $user, RoleEnum|string $role)
    {
        $this->handle($roleable, $user, $role);
    }
}
