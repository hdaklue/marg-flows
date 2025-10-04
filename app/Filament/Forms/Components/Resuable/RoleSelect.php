<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components\Resuable;

use Filament\Forms\Components\Select;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Contracts\RoleContract;
use Hdaklue\Porter\RoleFactory;

final class RoleSelect
{
    public static function make(
        string $id,
        RoleableEntity $roleableEntity,
        AssignableEntity $assignableEntity,
        bool $required = true,
        ?RoleContract $currentRole = null,
    ): Select {
        return Select::make($id)
            ->required($required)
            ->label('Select Roles')
            ->disableOptionWhen(fn (string $value) => $value === $currentRole?->getPlainKey())

            ->options(self::resolveRoles($assignableEntity, $roleableEntity));
    }

    private static function resolveRoles(
        AssignableEntity $assignableEntity,
        RoleableEntity $roleableEntity,
    ) {
        return RoleFactory::getRolesLowerThanOrEqual($assignableEntity->getAssignmentOn($roleableEntity))
            ->sortByDesc(fn (RoleContract $item) => (int) $item->getLevel())
            ->mapWithKeys(fn ($item) => [
                $item::getPlainKey() => $item->getLabel(),
            ]);
    }
}
