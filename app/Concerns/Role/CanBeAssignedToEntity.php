<?php

declare(strict_types=1);

namespace App\Concerns\Role;

use App\Contracts\Role\RoleableEntity;
use App\Enums\Role\RoleEnum;
use App\Facades\RoleManager;
use App\Models\ModelHasRole;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait CanBeAssignedToEntity
{
    /**
     * roleAssignments.
     */
    public function roleAssignments(): MorphMany
    {
        return $this->morphMany(ModelHasRole::class, 'model');
    }

    public function getAssignedEntitiesByType(string $type): Collection
    {
        return RoleManager::getAssignedEntitiesByType($this, $type);
    }

    /**
     * hasAssignmentOn.
     */
    public function hasAssignmentOn(RoleableEntity $target, string|RoleEnum $roleName): bool
    {
        return RoleManager::hasRoleOn($this, $target, $roleName);
        // if ($roleName instanceof RoleEnum) {
        //     $roleName = $roleName->value;
        // }

        // if ($target instanceof Tenant) {
        //     $roleToCheck = $target->systemRoles()->where('name', $roleName)->firstOrFail();
        // } else {
        //     $target->loadMissing('tenant');
        //     $roleToCheck = $target->getTenant()->systemRoles()->where('name', $roleName)->firstOrFail();
        // }

        // return ModelHasRole::where('model_type', $this->getMorphClass())
        //     ->where('model_id', $this->getKey())
        //     ->where('roleable_type', $target->getMorphClass())
        //     ->where('roleable_id', $target->getKey())
        //     ->where('role_id', $roleToCheck->getKey())
        //     ->exists();

    }

    /**
     * isAssignedTo.
     */
    public function isAssignedTo(RoleableEntity $entity): bool
    {
        return RoleManager::hasAnyRoleOn($this, $entity);
        // return $this->roleAssignments()
        //     ->where('model_type', $this->getMorphClass())
        //     ->where('model_id', $this->getKey())
        //     ->where('roleable_type', $entity->getMorphClass())
        //     ->where('roleable_id', $entity->getKey())
        //     ->exists();
    }

    /**
     * getAssignmentOn.
     */
    public function getAssignmentOn(RoleableEntity $entity): ?Role
    {
        return RoleManager::getRoleOn($this, $entity);

        // return $this->roleAssignments()
        //     ->with('role')
        //     ->where('roleable_type', $entity->getMorphClass())
        //     ->where('roleable_id', $entity->getKey())
        //     ->firstOrFail()->getRole();

    }

    #[Scope]
    public function scopeAssignedTo(Builder $builder, RoleableEntity $entity): Builder
    {
        return $builder->whereHas('roleAssignments', function ($query) use ($entity) {
            $query->where('roleable_type', $entity->getMorphClass())
                ->where('roleable_id', $entity->getKey());
        });
    }

    #[Scope]
    public function scopeNotAssignedTo(Builder $builder, RoleableEntity $entity): Builder
    {
        return $builder->whereDoesntHave('roleAssignments', function ($query) use ($entity) {
            $query->where('roleable_type', $entity->getMorphClass())
                ->where('roleable_id', $entity->getKey());
        });
    }
}
