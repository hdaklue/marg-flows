<?php

declare(strict_types=1);

namespace App\Services\Role;

use App\Contracts\Role\AssignableEntity;
use App\Contracts\Role\RoleableEntity;
use App\Contracts\Role\RoleAssignmentManagerInterface;
use App\Enums\Role\RoleEnum;
use App\Models\ModelHasRole;
use App\Models\Role;
use App\Models\Tenant;
use DomainException;
use Illuminate\Support\Collection;

final class RoleAssignmentService implements RoleAssignmentManagerInterface
{
    public function assign(AssignableEntity $user, RoleableEntity $target, string|RoleEnum $role): void
    {
        $roleToAssign = $this->ensureRoleBelongsToTenant($target, $role);
        ModelHasRole::firstOrCreate([
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'roleable_type' => $target->getMorphClass(),
            'roleable_id' => $target->getKey(),
            'role_id' => $roleToAssign->getKey(),
        ]);
    }

    public function remove(AssignableEntity $user, RoleableEntity $target): void
    {
        ModelHasRole::where([
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
        ])->delete();
    }

    public function getParticipantsHasRole(RoleableEntity $target, string|RoleEnum $role): Collection
    {
        $roleToCheck = $this->ensureRoleBelongsToTenant($target, $role);

        return ModelHasRole::where([
            'roleable_type' => $target->getMorphClass(),
            'roleable_id' => $target->getKey(),
            'role_id' => $roleToCheck->getKey(),
        ])->with('model')->get()->pluck('model');
    }

    public function getParticipantsWithRoles(RoleableEntity $target): Collection
    {
        return ModelHasRole::where([
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
        ])
            ->with(['model', 'role'])
            ->get();
    }

    public function getParticipants(RoleableEntity $target): Collection
    {
        return $this->getParticipantsWithRoles($target)->pluck('model');
    }

    public function hasRoleOn(AssignableEntity $user, RoleableEntity $target, string|RoleEnum $role): bool
    {
        $roleToCheck = $this->ensureRoleBelongsToTenant($target, $role);

        return ModelHasRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
            'role_id' => $roleToCheck->getKey(),
        ])->exists();
    }

    public function hasAnyRoleOn(AssignableEntity $user, RoleableEntity $target): bool
    {
        return ModelHasRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
        ])->exists();
    }

    public function getRoleOn(AssignableEntity $user, RoleableEntity $target): ?Role
    {
        return ModelHasRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
            'roleable_type' => $target->getMorphClass(),
            'roleable_id' => $target->getKey(),
        ])->first()?->getRole();
    }

    public function ensureRoleBelongsToTenant(RoleableEntity $target, string|RoleEnum $roleName): Role
    {
        $roleName = $this->resolveRoleName($roleName);

        $tenant = $this->resolveTargetTenant($target);

        $role = $tenant->systemRoleByName($roleName);

        if (! $role) {
            logger()->warning('Missing tenant role', [
                'role' => $roleName,
                'tenant' => $target->getTenantId(),
                'target' => get_class($target),
            ]);
            throw new DomainException("Role '{$roleName}' does not belong to tenant '{$target->getTenantId()}'.");
        }

        return $role;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(AssignableEntity $user, RoleableEntity $target): void {}

    /**
     * {@inheritDoc}
     */
    public function generateCacheKey(AssignableEntity $user, RoleableEntity $target, Role $role): string {}

    private function resolveTargetTenant(RoleableEntity $target)
    {
        if (! $target instanceof Tenant) {
            $target->loadMissing('tenant');

            return $target->getTenant();
        }

        return $target;
    }

    private function resolveRoleName($roleName)
    {
        $roleName = $roleName instanceof RoleEnum ? $roleName->value : $roleName;

        return $roleName;
    }
}
