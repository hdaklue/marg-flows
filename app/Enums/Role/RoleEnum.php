<?php

declare(strict_types=1);

namespace App\Enums\Role;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;

enum RoleEnum: string implements HasDescription, HasLabel
{
    /**
     * Super Admin - System-wide administrative access
     * Can manage tenants, system settings, and all entities.
     */
    // case SUPER_ADMIN = 'super_admin';

    /**
     * Tenant Admin - Tenant-wide administrative access
     * Can manage all entities within their tenant.
     */
    // case TENANT_ADMIN = 'tenant_admin';

    /**
     * Admin - Entity administrative access
     * Can manage specific entities (projects, organizations, etc.).
     */
    case ADMIN = 'admin';

    /**
     * Manager - Entity management access
     * Can manage entity content and assign viewer/editor roles.
     */
    case MANAGER = 'manager';

    /**
     * Editor - Content modification access
     * Can create, edit, and delete content within entities.
     */
    case EDITOR = 'editor';

    /**
     * Contributor - Limited content access
     * Can create and edit their own content.
     */
    case CONTRIBUTOR = 'contributor';

    /**
     * Viewer - Read-only access
     * Can view content within entities.
     */
    case VIEWER = 'viewer';

    /**
     * Guest - Minimal access
     * Limited read access to public content.
     */
    case GUEST = 'guest';

    public static function whereLowerThanOrEqual(RoleEnum $other): Collection
    {
        return \collect(self::cases())
            ->filter(fn ($role) => $role->isLowerThanOrEqual($other))
            ->mapWithKeys(fn ($item) => [$item->value => $item->getLabel()]);

    }

    public static function getRolesLowerThanOrEqual(RoleEnum $role)
    {
        return collect(self::cases())->reject(function ($case) use ($role) {
            return $case->getLevel() > $role->getLevel();
        })->map(fn ($item) => ['value' => $item->value, 'label' => $item->getLabel()]);
    }

    /**
     * Get the hierarchical level of this role
     * Higher numbers = more privileges.
     */
    public function getLevel(): int
    {
        return match ($this) {
            // self::SUPER_ADMIN => 10,
            // self::TENANT_ADMIN => 8,
            self::ADMIN => 6,
            self::MANAGER => 5,
            self::EDITOR => 4,
            self::CONTRIBUTOR => 3,
            self::VIEWER => 2,
            self::GUEST => 1,
        };
    }

    /**
     * Get human-readable label for this role.
     */
    public function getLabel(): string
    {
        return match ($this) {
            // self::SUPER_ADMIN => __('app.system_roles.super_admin'),
            // self::TENANT_ADMIN => __('app.system_roles.tenant_admin'),
            self::ADMIN => __('app.system_roles.admin'),
            self::MANAGER => __('app.system_roles.manager'),
            self::EDITOR => __('app.system_roles.editor'),
            self::CONTRIBUTOR => __('app.system_roles.contributor'),
            self::VIEWER => __('app.system_roles.viewer'),
            self::GUEST => __('app.system_roles.guest'),
        };
    }

    /**
     * Get description of role capabilities.
     */
    public function getDescription(): string
    {
        return match ($this) {
            // self::SUPER_ADMIN => __('app.system_role_descriptions.super_admin'),
            // self::TENANT_ADMIN => __('app.system_role_descriptions.tenant_admin'),
            self::ADMIN => __('app.system_role_descriptions.admin'),
            self::MANAGER => __('app.system_role_descriptions.manager'),
            self::EDITOR => __('app.system_role_descriptions.editor'),
            self::CONTRIBUTOR => __('app.system_role_descriptions.contributor'),
            self::VIEWER => __('app.system_role_descriptions.viewer'),
            self::GUEST => __('app.system_role_descriptions.guest'),
        };
    }

    /**
     * Check if this role is higher than another role.
     */
    public function isHigherThan(RoleEnum $other): bool
    {
        return $this->getLevel() > $other->getLevel();
    }

    /**
     * Check if this role is lower than another role.
     */
    public function isLowerThan(RoleEnum $other): bool
    {
        return $this->getLevel() < $other->getLevel();
    }

    public function isLowerThanOrEqual(RoleEnum $other): bool
    {
        return $this->getLevel() <= $other->getLevel();
    }

    public function isEqualTo(RoleEnum $other): bool
    {
        return $this->getLevel() === $other->getLevel();
    }

    /**
     * Check if this role is at least the same level as another.
     */
    public function isAtLeast(RoleEnum $other): bool
    {
        return $this->getLevel() >= $other->getLevel();
    }
}
