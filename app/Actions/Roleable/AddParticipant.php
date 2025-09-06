<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

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
        // The package will dispatch events that our event listeners will handle
        PackageAddParticipant::run($roleable, $user, $role);
    }

    public function asJob(RoleableEntity $roleable, AssignableEntity $user, RoleEnum|string $role)
    {
        $this->handle($roleable, $user, $role);
    }
}
