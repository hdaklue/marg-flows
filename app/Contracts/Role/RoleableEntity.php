<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Collections\Role\ParticipantsCollection;
use App\Enums\Role\RoleEnum;
use App\Models\Role;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @method load();
 * @method HasMany systemRoles()
 */
interface RoleableEntity extends Arrayable
{
    public function addParticipant(AssignableEntity $target, string|RoleEnum $role): void;

    /**
     * All role assignments attached to this entity.
     */
    public function roleAssignments(): MorphMany;

    public function assignedRoles(): MorphToMany;

    public function getParticipants(): ParticipantsCollection;

    public function getParticipantRole(AssignableEntity $participant): ?Role;

    /**
     * Just to enhance IDE support.
     */
    public function loadMissing($relations);

    public function participants(): MorphMany;

    public function getParticipant(AssignableEntity|string|int $entity): ?AssignableEntity;

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

    public function getTenant();
}
