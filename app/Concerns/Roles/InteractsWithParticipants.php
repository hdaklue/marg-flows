<?php

namespace App\Concerns\Roles;

use App\Enums\Role\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;

/**
 * Participants Trait
 *
 * Provides semantic participant methods that leverage the underlying RoleableEntity
 * trait. This creates a clean, readable API for accessing users based on their roles
 * while maintaining performance through the existing caching infrastructure.
 *
 * This trait should be used alongside RoleableEntity trait to provide enhanced
 * participant-focused methods with better semantic meaning.
 *
 * @author Hassan Ibrahim
 *
 * @version 1.0.0
 *
 * @since 2025-06-18
 */
trait InteractsWithParticipants
{
    // /**
    //  * Get all participants (users with any role) on this entity
    //  */
    // public function getParticipants(): Collection
    // {
    //     return $this->usersWithAnyRole()->get();
    // }

    // /**
    //  * Get all administrators of this entity
    //  */
    // public function getAdmins(): Collection
    // {
    //     return $this->usersWithRole(RoleEnum::ADMIN->value)->get();
    // }

    // /**
    //  * Get all viewers of this entity
    //  */
    // public function getViewers(): Collection
    // {
    //     return $this->usersWithRole('viewer')->get();
    // }

    // /**
    //  * Get all reviewers of this entity
    //  */
    // public function getReviewers(): Collection
    // {
    //     return $this->usersWithRole('reviewer')->get();
    // }

    // /**
    //  * Get all managers of this entity (if role exists)
    //  */
    // public function getManagers(): Collection
    // {
    //     return $this->usersWithRole('manager')->get();
    // }

    // /**
    //  * Get all contributors of this entity (if role exists)
    //  */
    // public function getContributors(): Collection
    // {
    //     return $this->usersWithRole('contributor')->get();
    // }

    // /**
    //  * Get entities for a specific user
    //  */
    // public function scopeForParticipant(Builder $query, Model $member): Builder
    // {
    //     return $query->whereAttachedTo($member, 'participantsQuery');
    // }

    // // ========================================================================
    // // Query Builder Methods (for flexibility and performance)
    // // ========================================================================

    // /**
    //  * Get participants query builder for additional filtering
    //  */
    // public function participants()
    // {
    //     return $this->usersWithAnyRole();
    // }

    // /**
    //  * Get admins query builder for additional filtering
    //  */
    // public function admins()
    // {
    //     return $this->usersWithRole('admin');
    // }

    // /**
    //  * Get viewers query builder for additional filtering
    //  */
    // public function viewers()
    // {
    //     return $this->usersWithRole('viewer');
    // }

    /**
     * Get reviewers query builder for additional filtering
     */
    public function reviewersQuery()
    {
        return $this->usersWithRole('reviewer');
    }

    // // ========================================================================
    // // Boolean Check Methods
    // // ========================================================================

    // /**
    //  * Check if a user is a participant (has any role) on this entity
    //  */
    // public function isParticipant(Model $user): bool
    // {
    //     return $this->userHasAnyRole($user);
    // }

    // /**
    //  * Check if a user is an admin of this entity
    //  */
    // public function isAdmin(Model $user): bool
    // {
    //     return $this->userHasRole($user, 'admin');
    // }

    // /**
    //  * Check if a user is a viewer of this entity
    //  */
    // public function isViewer(Model $user): bool
    // {
    //     return $this->userHasRole($user, 'viewer');
    // }

    // /**
    //  * Check if a user is a reviewer of this entity
    //  */
    // public function isReviewer(Model $user): bool
    // {
    //     return $this->userHasRole($user, 'reviewer');
    // }

    // /**
    //  * Check if a user is a manager of this entity
    //  */
    // public function isManager(Model $user): bool
    // {
    //     return $this->userHasRole($user, 'manager');
    // }

    // /**
    //  * Check if a user is a contributor of this entity
    //  */
    // public function isContributor(Model $user): bool
    // {
    //     return $this->userHasRole($user, 'contributor');
    // }

    // ========================================================================
    // Count Methods (clean semantic API)
    // ========================================================================

    /**
     * Get count of participants on this entity
     */
    public function participantsCount(): int
    {
        return $this->participantsQuery()->count();
    }

    /**
     * Get count of admins on this entity
     */
    public function adminsCount(): int
    {
        return $this->adminsQuery()->count();
    }

    /**
     * Get count of writers on this entity
     */
    public function writersCount(): int
    {
        return $this->writersQuery()->count();
    }

    /**
     * Get count of viewers on this entity
     */
    public function viewersCount(): int
    {
        return $this->viewersQuery()->count();
    }

    /**
     * Get count of reviewers on this entity
     */
    public function reviewersCount(): int
    {
        return $this->reviewersQuery()->count();
    }

    // ========================================================================
    // Convenience Methods for Common Role Operations
    // ========================================================================

    /**
     * Add a participant with specific role(s)
     */
    public function addParticipant(Model $user, string|array $roles): self
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->assignUserRoles($user, $roles);
    }

    /**
     * Remove a participant's specific role(s)
     */
    public function removeParticipant(Model $user, ?string $role = null): self
    {
        if ($role) {
            return $this->removeUserRole($user, $role);
        }

        return $this->removeAllUserRoles($user);
    }

    /**
     * Promote a user to admin
     */
    public function promoteToAdmin(Model $user): self
    {
        return $this->assignUserRole($user, 'admin');
    }

    /**
     * Demote an admin to writer
     */
    public function demoteToWriter(Model $user): self
    {
        return $this->removeUserRole($user, 'admin')
            ->assignUserRole($user, RoleEnum::WRITER->value);
    }

    /**
     * Get users who can edit this entity (admins + writers)
     */
    public function editorsQuery()
    {
        return $this->usersWithRole(['admin', RoleEnum::WRITER->value]);
    }

    /**
     * Get users who can edit this entity (admins + writers)
     */
    public function editors(): Collection
    {
        return $this->editorsQuery()->get();
    }

    /**
     * Check if user can edit this entity
     */
    public function canEdit(Model $user): bool
    {
        return $this->userHasRole($user, ['admin', RoleEnum::WRITER->value]);
    }

    /**
     * Check if user can view this entity
     */
    public function canView(Model $user): bool
    {
        return $this->userHasAnyRole($user);
    }
}
