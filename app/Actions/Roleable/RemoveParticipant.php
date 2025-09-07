<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use Exception;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Facades\Porter;
use Lorisleiva\Actions\Concerns\AsAction;

final class RemoveParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user)
    {
        // Call the package action to handle the core functionality
        // The package will dispatch events that our event listeners will handle
        try {
            Porter::remove($user, $roleable);
        } catch (Exception $e) {
            logger()->error('Error Removing Participant', [
                'roleable' => $roleable,
                'assignable' => $user,
            ]);
            throw $e;
        }
    }
}
