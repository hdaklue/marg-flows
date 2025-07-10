<?php

declare(strict_types=1);

namespace App\Contracts\Role;

use App\Enums\Role\RoleEnum;
use App\Models\ModelHasRole;
use App\Models\Role;
use DomainException;
use Illuminate\Support\Collection;

interface RoleAssignmentManagerInterface
{
    /**
     * Assign a role to a user or assignable entity on a target.
     */
    public function assign(AssignableEntity $user, RoleableEntity $target, string|RoleEnum $role): void;

    /**
     * Revoke a role from a user or assignable entity on a target.
     */
    public function remove(AssignableEntity $user, RoleableEntity $target): void;

    /**
     * Determine if the user/entity has a specific role on the target.
     */
    public function hasRoleOn(AssignableEntity $user, RoleableEntity $target, string|RoleEnum $role): bool;

    /**
     * Determine if the user/entity has any role on the target.
     */
    public function hasAnyRoleOn(AssignableEntity $user, RoleableEntity $target): bool;

    /**
     * Get all users/entities assigned a specific role on the target.
     *
     *
     * @return Collection<AssignableEntity>
     */
    public function getParticipantsHasRole(RoleableEntity $target, string|RoleEnum $role): Collection;

    /**
     * Get all users/entities assigned to the target, regardless of role.
     *
     *
     * @return Collection<ModelHasRole>
     */
    public function getParticipantsWithRoles(RoleableEntity $target): Collection;

    /**
     * Get all users/entities assigned to the target.
     *
     *
     * @return Collection<ModelHasRole>
     */
    public function getParticipants(RoleableEntity $target): Collection;

    /**
     * Clear the role assignment cache for a specific user and target.
     */
    public function clearCache(RoleableEntity $target);

    /**
     * Summary of bulkClearCache.
     *
     * @param  Collection<RoleableEntity>  $targets
     */
    public function bulkClearCache(Collection $targets);

    public function getRoleOn(AssignableEntity $user, RoleableEntity $target): ?Role;

    /**
     * Generate a cache key for a role assignment.
     */
    public function generateParticipantsCacheKey(RoleableEntity $target): string;

    /**
     * Ensure that a role belongs to the tenant associated with the target.
     *
     * @throws DomainException if the role does not belong to the correct tenant.
     */
    public function ensureRoleBelongsToTenant(RoleableEntity $target, string|RoleEnum $roleName): Role;
}
