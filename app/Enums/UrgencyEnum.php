<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UrgencyEnum: int implements HasColor, HasLabel
{
    case NORMAL = 1;
    case HIGHT = 2;

    public static function asSelectArray(): array
    {
        return [
            self::NORMAL->value => self::NORMAL->getLabel(),
            self::HIGHT->value => self::HIGHT->getLabel(),
        ];
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::HIGHT => 'Hight',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::NORMAL => 'zinc',
            self::HIGHT => 'red',
        };
    }

    public function isHigh(): bool
    {
        return $this === self::HIGHT;
    }

    public function isLow(): bool
    {
        return $this === self::NORMAL;
    }
}
