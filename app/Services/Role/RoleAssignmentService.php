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
use Illuminate\Support\Facades\Cache;

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

        $this->clearCache($target);
    }

    public function remove(AssignableEntity $user, RoleableEntity $target): void
    {
        ModelHasRole::where([
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
        ])->delete();

        $this->clearCache($target);
    }

    public function getParticipantsHasRole(RoleableEntity $target, string|RoleEnum $role): Collection
    {
        $roleToCheck = $this->ensureRoleBelongsToTenant($target, $role);

        if (config('role.should_cache')) {
            return $this->getParticipants($target)->filter(function (ModelHasRole $participant) use ($roleToCheck) {
                return $participant->role->getKey() === $roleToCheck->getKey();
            })->pluck('model');
        }

        return ModelHasRole::where([
            'roleable_type' => $target->getMorphClass(),
            'roleable_id' => $target->getKey(),
            'role_id' => $roleToCheck->getKey(),
        ])->with('model')->get()->pluck('model');
    }

    /**
     * Summary of getParticipantsWithRoles.
     *
     * @return Collection<ModelHasRole>
     */
    public function getParticipantsWithRoles(RoleableEntity $target): Collection
    {
        if (config('role.should_cache')) {
            return Cache::remember($this->generateParticipantsCacheKey($target), now()->addHour(), function () use ($target) {
                logger()->info('hitting cache');

                return ModelHasRole::where([
                    'roleable_id' => $target->getKey(),
                    'roleable_type' => $target->getMorphClass(),
                ])
                    ->with(['model', 'role'])
                    ->get();
            });
        }

        return ModelHasRole::where([
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
        ])
            ->with(['model', 'role'])
            ->get();
    }

    /**
     * Summary of getParticipantsHasRole.
     *
     * @return Collection<ModelHasRole>
     */
    public function getParticipants(RoleableEntity $target): Collection
    {
        return $this->getParticipantsWithRoles($target);
    }

    public function hasRoleOn(AssignableEntity $user, RoleableEntity $target, string|RoleEnum $role): bool
    {
        // $roleToCheck = $this->ensureRoleBelongsToTenant($target, $role);
        $roleName = $this->resolveRoleName($role);

        if (config('role.should_cache')) {

            return $this->getParticipants($target)->filter(function (ModelHasRole $participant) use ($user, $roleName) {
                return $participant->role->name === $roleName &&
                $participant->model->getKey() === $user->getKey() &&
                $participant->model->getMorphClass() === $user->getMorphClass();
            })->isNotEmpty();
        }

        return ModelHasRole::where([
            'model_id' => $user->getKey(),
            'model_type' => $user->getMorphClass(),
            'roleable_id' => $target->getKey(),
            'roleable_type' => $target->getMorphClass(),
        ])->whereHas('role', function ($auery) use ($roleName) {
            $auery->where('name', $roleName);
        })->exists();
    }

    public function hasAnyRoleOn(AssignableEntity $user, RoleableEntity $target): bool
    {
        if (config('role.should_cache')) {
            return $this->getParticipants($target)->filter(function (ModelHasRole $participant) use ($user) {
                return $participant->model->getKey() === $user->getKey() &&
                $participant->model->getMorphClass() === $user->getMorphClass();
            })->isNotEmpty();
        }

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
    public function clearCache(RoleableEntity $target)
    {
        logger()->info('clearing cache');
        $key = $this->generateParticipantsCacheKey($target);
        Cache::forget($key);
    }

    // /**
    //  * {@inheritDoc}
    //  */
    // public function generateCacheKey(AssignableEntity $user, RoleableEntity $target, Role $role): string
    // {
    //     return 'shold be implemented';
    // }

    public function generateParticipantsCacheKey(RoleableEntity $target): string
    {
        return "parcipitans:{$target->getMorphClass()}:{$target->getKey()}";
    }

    /** @param Collection<RoleableEntity> $targets */
    public function bulkClearCache(Collection $targets)
    {
        $targets->each(function (RoleableEntity $target) {
            return $this->clearCache($target);
        });

    }

    private function resolveTargetTenant(RoleableEntity $target)
    {
        if (! $target instanceof Tenant) {
            $target->loadMissing('tenant');

            return $target->getTenant();
        }

        return $target;
    }

    private function resolveRoleName(string|RoleEnum $roleName): string
    {
        return $roleName instanceof RoleEnum ? $roleName->value : $roleName;

    }
}
