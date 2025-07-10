<?php

declare(strict_types=1);

namespace App\Concerns\Role;

use App\Contracts\Role\AssignableEntity;
use App\Enums\Role\RoleEnum;
use App\Facades\RoleManager;
use App\Models\ModelHasRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

trait ManagesParticipants
{
    public function roleAssignments(): MorphMany
    {
        return $this->morphMany(ModelHasRole::class, 'roleable');
    }

    public function assignedRoles(): MorphToMany
    {

        return $this->morphToMany(Role::class, 'roleable', config('role.table_names.model_has_roles'));
    }

    public function isAdmin(AssignableEntity $entity): bool
    {
        return RoleManager::hasRoleOn($entity, $this, RoleEnum::ADMIN);
    }

    public function participants(): MorphMany
    {
        return $this->roleAssignments()
            ->where('model_type', Relation::getMorphAlias(User::class))
            ->with(['model', 'role']);
    }

    public function getParticipants(): Collection
    {
        return RoleManager::getParticipants($this);
    }

    public function addParticipant(AssignableEntity $user, RoleEnum|string $role, bool $silently = false): void
    {

        RoleManager::assign($user, $this, $role);
    }

    public function removeParticipant(AssignableEntity $user, ?bool $silently = false): void
    {
        RoleManager::remove($user, $this);

        if (! $silently) {
            // fire event
        }
    }

    public function isParticipant(AssignableEntity $entity): bool
    {
        return RoleManager::hasAnyRoleOn($entity, $this);
    }

    // public function changeParticipantRole(AssignableEntity $user, Role $newRole): void
    // {
    //     $this->removeParticipant($user, true);
    //     $this->addParticipant($user, $newRole, true);
    // }

    public function getParticipantRole(AssignableEntity $entity): ?Role
    {
        return RoleManager::getRoleOn($entity, $this);
    }

    public function scopeForParticipant(Builder $query, AssignableEntity $member): Builder
    {
        return $query->whereHas('roleAssignments', function ($q) use ($member) {
            $q->where('model_type', $member->getMorphClass())
                ->where('model_id', $member->getKey());
        });
    }
}
