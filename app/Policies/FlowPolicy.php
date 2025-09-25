<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Flow;
use App\Models\User;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\RoleFactory;

final class FlowPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAssignedTo(filamentTenant());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Flow $flow): bool
    {
        $flow->loadMissing('tenant');

        return
            $user->isAssignedTo($flow)
            || $user->isAtLeastOn(RoleFactory::admin(), $flow->tenant);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, RoleableEntity $tenant): bool
    {
        return $user->isAtLeastOn(RoleFactory::manager(), $tenant);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Flow $flow): bool
    {
        return
            $user->hasAssignmentOn($flow, RoleFactory::admin())
            || $user->hasAssignmentOn($flow, RoleFactory::manager());

        // return $user->hasAssignmentOn('writer', $flow) || $user->hasAssignmentOn(RoleEnum::SUPER_ADMIN, filament()->getTenant());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Flow $flow): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Flow $flow): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Flow $flow): bool
    {
        return true;
    }

    public function manage(User $user, Flow $flow): bool
    {
        return $user->isAtLeastOn(RoleFactory::manager(), $flow);
    }
}
