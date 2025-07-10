<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Contracts\Tenant\BelongsToTenantContract;
use App\Models\Role;
use Illuminate\Contracts\Support\Arrayable;
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

    /**
     * Get the morph class (used in roleable_type).
     */
    public function getMorphClass();

    /**
     * Unique identifier of the entity (roleable_id).
     */
    public function getKey();
}
