<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum FlowStatus: int implements HasColor, HasLabel
{
    use IsKanbanStatus;

    case SCHEDULED = 1;
    case ACTIVE = 2;
    case PAUSED = 3;
    case BLOCKED = 4;
    case COMPLETED = 5;
    case CANCELED = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'active',
            self::PAUSED => 'paused',
            self::BLOCKED => 'blocked',
            self::COMPLETED => 'completed',
            self::CANCELED => 'canceled',
            self::SCHEDULED => 'scheduled',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'sky',
            self::PAUSED => 'yellow',
            self::BLOCKED => 'red',
            self::COMPLETED => 'green',
            self::SCHEDULED => 'indigo',
            default => 'gray',

        };
    }

    public function getTitle(): string
    {
        return $this->getLabel();
    }
}
