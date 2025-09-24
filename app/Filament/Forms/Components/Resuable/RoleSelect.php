<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components\Resuable;

use Filament\Forms\Components\Select;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\RoleFactory;

final class RoleSelect
{
    public static function make(
        string $id,
        RoleableEntity $roleableEntity,
        AssignableEntity $assignableEntity,
        bool $required = true,
    ): Select {
        return Select::make($id)
            ->required($required)
            ->label('Select Roles')
            ->options(self::resolveRoles($assignableEntity, $roleableEntity));
    }

    private static function resolveRoles(
        AssignableEntity $assignableEntity,
        RoleableEntity $roleableEntity,
    ) {
        return RoleFactory::getRolesLowerThan($assignableEntity->getAssignmentOn($roleableEntity))
            ->sortByDesc(fn($item) => (int) $item->getLevel())
            ->mapWithKeys(fn($item) => [
                $item::getPlainKey() => $item->getLabel(),
            ]);
    }
}
