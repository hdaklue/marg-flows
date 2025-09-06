<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use Hdaklue\MargRbac\Contracts\Role\AssignableEntity;
use Hdaklue\MargRbac\Contracts\Role\RoleableEntity;
use Hdaklue\MargRbac\Actions\Roleable\RemoveParticipant as PackageRemoveParticipant;
use Lorisleiva\Actions\Concerns\AsAction;

final class RemoveParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user)
    {
        // Call the package action to handle the core functionality
        // The package will dispatch events that our event listeners will handle
        PackageRemoveParticipant::run($roleable, $user);
    }
}
