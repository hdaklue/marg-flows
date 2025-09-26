<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Hdaklue\Porter\RoleFactory;

final class TenantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAssignedTo($tenant);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isAtLeastOn(RoleFactory::admin(), $tenant);
    }

    public function manage(User $user, Tenant $tenant): bool
    {
        return $user->isAtLeastOn(RoleFactory::admin(), $tenant);
    }

    /**
     * Determine whether the user can delete the model.
     */
    //    public function delete(User $user, Tenant $tenant): bool
    //    {
    //        return $user->isSuperAdmin();
    //    }

    /**
     * Determine whether the user can restore the model.
     */
    //    public function restore(User $user, Tenant $tenant): bool
    //    {
    //        return $user->isSuperAdmin();
    //    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    //    public function forceDelete(User $user, Tenant $tenant): bool
    //    {
    //        return $user->isSuperAdmin();
    //    }
}
