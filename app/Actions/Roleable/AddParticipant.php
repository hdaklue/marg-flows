<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use Exception;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Contracts\RoleContract;
use Lorisleiva\Actions\Concerns\AsAction;

final class AddParticipant
{
    use AsAction;

    public function handle(RoleableEntity $roleable, AssignableEntity $user, RoleContract $role)
    {
        try {
            $roleable->assign($user, $role);
        } catch (Exception $e) {
            logger()->error('Error Adding Participant', [
                'roleable' => $roleable,
                'role' => $role,
                'assignable' => $user,
            ]);
            throw $e;
        }
    }

    public function asJob(RoleableEntity $roleable, AssignableEntity $user, RoleContract $role)
    {
        $this->handle($roleable, $user, $role);
    }
}
