<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Role\AssignableEntity;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use App\Models\Flow;
use App\Models\User;

final class FlowPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return filamentTenant()->isParticipant($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AssignableEntity $user, Flow $flow): bool
    {

        return $flow->isParticipant($user) || $flow->getTenant()->isAdmin($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAssignmentOn(filamentTenant(), RoleEnum::ADMIN)
        || $user->hasAssignmentOn(filamentTenant(), RoleEnum::MANAGER);

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Flow $flow): bool
    {

        return $user->hasAssignmentOn($flow, RoleEnum::ADMIN) || $user->hasAssignmentOn($flow, RoleEnum::MANAGER);

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

    public function manageMembers(User $user, Flow $flow): bool
    {
        return $user->hasAssignmentOn($flow, RoleEnum::ADMIN) || $user->hasAssignmentOn($flow, RoleEnum::MANAGER);
    }

    public function manageFlow(User $user, Flow $flow): bool
    {

        return $user->hasAssignmentOn($flow, RoleEnum::ADMIN) || $user->hasAssignmentOn($flow, RoleEnum::MANAGER);
    }
}
