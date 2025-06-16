<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FlowStatus: int implements HasColor, HasLabel
{
    case ACTIVE = 1;
    case SCHEDULED = 2;
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
            self::SCHEDULED => 'scheduled'

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'primary',
            self::PAUSED => 'warning',
            self::BLOCKED => 'warning',
            self::COMPLETED => 'success',
            default => 'gray',

        };
    }
}
