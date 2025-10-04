<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Document\Documentable;
use App\Models\Document;
use App\Models\User;
use Hdaklue\Porter\RoleFactory;

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
        return $user->isAtLeastOn(RoleFactory::editor(), $documentable);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        return $user->isAtLeastOn(RoleFactory::editor(), $document);
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

    public function manage(User $user, Document $document): bool
    {
        return $user->isAtLeastOn(RoleFactory::manager(), $document) && ! $document->isArchived();
    }
}
