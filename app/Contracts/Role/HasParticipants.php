<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Contracts\Tenant\BelongsToTenantContract;
use App\Enums\Role\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Has Participants Interface
 *Defines the contract for entities that can have participants (users with roles).
 * This interface provides semantic methods for accessing users based on their roles
 * on the entity, making role-based queries more readable and consistent.
 *
 * @property Collection $participants
 *
 * @author Hassan Ibrahim
 *
 * @version 1.0.0
 *
 * @since 2025-06-18
 */
interface HasParticipants extends BelongsToTenantContract
{
    public function participants(): MorphMany;

    public function getParticipants(): Collection;

    /**
     * Add a participant with specific role(s).
     */
    public function addParticipant(AssignableEntity $user, string|RoleEnum $role, bool $silently = false);

    /**
     * Remove a participant's specific role(s).
     */
    public function removeParticipant(AssignableEntity $user, ?bool $silently = false);

    /**
     * Change a participant's role(s)
     * this will revoke all roles and assign given role.
     */
    public function changeParticipantRole(AssignableEntity $user, Role $newRole);

    /**
     * Get scoped participants query builder for additional filtering.
     */
    public function scopeForParticipant(Builder $query, AssignableEntity $member): Builder;
}
