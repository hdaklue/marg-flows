<?php

declare(strict_types=1);

namespace App\Actions\Roleable;

use App\Contracts\Roles\Roleable;
use App\Enums\Role\RoleEnum;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Participant\AssignedToEntity;
use Lorisleiva\Actions\Concerns\AsAction;

class AddParticipant
{
    use AsAction;

    public function handle(Roleable $roleable, User $user, RoleEnum $role)
    {
        $roleable->assignUserRole($user, $role->value);
        $user->notify(new AssignedToEntity($roleable, $role->getLabel()));
    }

    public function asJob(Roleable $roleable, User $user, RoleEnum $role, Tenant $tenant)
    {
        \setPermissionsTeamId($tenant->id);
        $this->handle($roleable, $user, $role);
    }
}
