<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum FlowStage: int implements HasColor, HasLabel
{
    use IsKanbanStatus;

    case DRAFT = 1;
    case ACTIVE = 2;
    case PAUSED = 3;
    case BLOCKED = 4;
    case COMPLETED = 5;
    case CANCELED = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PAUSED => 'Pased',
            self::BLOCKED => 'Blocked',
            self::COMPLETED => 'Completed',
            self::CANCELED => 'Canceled',
            self::DRAFT => 'Drafted',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'sky',
            self::PAUSED => 'yellow',
            self::BLOCKED => 'red',
            self::COMPLETED => 'green',
            self::DRAFT => 'indigo',
            default => 'slate',

        };
    }

    public function getFilamentColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'primary',
            self::PAUSED => 'warning',
            self::BLOCKED => 'danger',
            self::COMPLETED => 'success',
            self::DRAFT => 'info',
            default => 'gray',

        };
    }

    public function getTitle(): string
    {
        return $this->getLabel();
    }
}
