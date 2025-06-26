<?php

declare(strict_types=1);

namespace App\Contracts\Roles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

interface Roleable
{
    // ========================================================================
    // Core Query Builder Methods
    // ========================================================================

    public function roles(): MorphToMany;

    public function usersWithRole($roles, ?string $guard = null): MorphToMany;

    public function usersWithAnyRole(?string $guard = null): MorphToMany;

    // ========================================================================
    // Core Role Checking
    // ========================================================================

    public function userHasRole(Model $user, $role, ?string $guard = null): bool;

    public function userHasAnyRole(Model $user): bool;

    // ========================================================================
    // Core Role Management
    // ========================================================================
    public function assignUserRole(Model $user, $role, bool $silently = false);

    public function removeUserRole(Model $user, $role);

    public function removeAllUserRoles(Model $user, bool $silently = false);

    public function assignUserRoles(Model $user, array $roles, bool $silently = false);

    // ========================================================================
    // Role Information
    // ========================================================================

    public function rolesForUser(Model $user): Collection;

    public function roleNamesForUser(Model $user): Collection;

    // ========================================================================
    // Cache Management
    // ========================================================================

    public function clearRoleCache(): void;

    public function warmRoleCache(array $users = [], ?string $guard = null): void;

    // ========================================================================
    // Bulk Operations
    // ========================================================================

    public function syncAllUserRoles(array $userRoleMap);
}
