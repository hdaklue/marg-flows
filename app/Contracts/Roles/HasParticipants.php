<?php

namespace App\Contracts\Roles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/* Has Participants Interface
*
* Defines the contract for entities that can have participants (users with roles).
* This interface provides semantic methods for accessing users based on their roles
* on the entity, making role-based queries more readable and consistent.
*
* @author Hassan Ibrahim
* @version 1.0.0
* @since 2025-06-18
*/
interface HasParticipants
{
    public function participants(): Relation;

    public function admins(): Relation;

    public function viewers(): Relation;

    public function contributors(): Relation;

    public function managers(): Relation;

    public function reviewers(): Relation;

    /**
     * Add a participant with specific role(s)
     */
    public function addParticipant(Model $user, string|array $roles, bool $silently = false);

    /**
     * Remove a participant's specific role(s)
     */
    public function removeParticipant(Model $user, ?string $role = null);

    /**
     * Change a participant's role(s)
     * this will revoke all roles and assign given role
     */
    public function changeParticipantRole(Model $user, string|\BackedEnum $role);

    /**
     * Check if a user is a participant (has any role) on this entity
     */
    public function isParticipant(Model $user): bool;

    /**
     * Check if a user is an admin of this entity
     */
    public function isAdmin(Model $user): bool;

    /**
     * Check if a user is a viewer of this entity
     */
    public function isViewer(Model $user): bool;

    /**
     * Check if a user is a contributor of this entity
     */
    public function isContributor(Model $user): bool;

    /**
     * Check if a user is a manager of this entity
     */
    public function isManager(Model $user): bool;

    /**
     * Check if a user is a reviewer of this entity
     */
    public function isReviewer(Model $user): bool;

    /**
     * Get scoped participants query builder for additional filtering
     */
    public function scopeForParticipant(Builder $query, Model $member): Builder;
}
