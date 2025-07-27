<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Document\Documentable;
use App\Enums\Role\RoleEnum;
use App\Models\Document;
use App\Models\User;

final class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        return $user->isAssignedTo($document);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Documentable $documentable): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->hasAssignmentOn($document, RoleEnum::ADMIN)
        || $user->hasAssignmentOn($document, RoleEnum::MANAGER);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    public function manageMembers(User $user, Document $document): bool
    {

        return $user->hasAssignmentOn($document, RoleEnum::ADMIN) || $user->hasAssignmentOn($document, RoleEnum::MANAGER);
    }
}
