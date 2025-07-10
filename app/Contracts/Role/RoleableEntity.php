<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Contracts\Tenant\BelongsToTenantContract;
use App\Enums\Role\RoleEnum;
use App\Models\Role;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * @method load();
 * @method HasMany systemRoles()
 */
interface RoleableEntity extends Arrayable, BelongsToTenantContract
{
    public function systemRoleByName(string $name): ?Role;

    public function getSystemRoles(): Collection;

    public function addParticipant(AssignableEntity $target, string|RoleEnum $role): void;

    /**
     * All role assignments attached to this entity.
     */
    public function roleAssignments(): MorphMany;

    public function assignedRoles(): MorphToMany;

    public function getParticipants(): Collection;

    public function getParticipantRole(AssignableEntity $participant): ?Role;

    /**
     * Just to enhance IDE support.
     */
    public function loadMissing($relations);

    public function participants(): MorphMany;

    /**
     * Remove a participant's specific role(s).
     */
    public function removeParticipant(AssignableEntity $user, ?bool $silently = false);

    /**
     * Get scoped participants query builder for additional filtering.
     */
    public function scopeForParticipant(Builder $query, AssignableEntity $member): Builder;

    /**
     * Get the morph class (used in roleable_type).
     */
    public function getMorphClass();

    /**
     * Unique identifier of the entity (roleable_id).
     */
    public function getKey();
}
