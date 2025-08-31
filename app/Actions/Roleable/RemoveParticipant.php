<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Contracts\Role\AssignableEntity;
use Hdaklue\MargRbac\Contracts\Role\RoleableEntity;
use App\Notifications\Participant\RemovedFromEntity;
use Hdaklue\MargRbac\Actions\Roleable\RemoveParticipant as PackageRemoveParticipant;
use Lorisleiva\Actions\Concerns\AsAction;

final class RemoveParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user)
    {
        // Call the package action to handle the core functionality
        PackageRemoveParticipant::run($roleable, $user);
        
        // Add app-specific logic after removal
        $user->notify(new RemovedFromEntity($roleable));
    }
}
