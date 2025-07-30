<?php

declare(strict_types=1);

namespace App\Traits;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

trait EnumSelectArrays
{
    public static function simpleArray(string|int $selected = null): array
    {
        return collect(static::cases())
            ->map(function ($case) use ($selected) {
                $item = [
                    'value' => $case->value,
                    'label' => $case instanceof HasLabel ? $case->getLabel() : $case->name,
                ];

                if ($selected !== null && $case->value === $selected) {
                    $item['selected'] = true;
                }

                return $item;
            })
            ->toArray();
    }

    public static function colorfulArray(string|int $selected = null): array
    {
        return collect(static::cases())
            ->map(function ($case) use ($selected) {
                $item = [
                    'value' => $case->value,
                    'label' => $case instanceof HasLabel ? $case->getLabel() : $case->name,
                ];

                if ($case instanceof HasColor) {
                    $item['color'] = $case->getColor();
                }

                if ($selected !== null && $case->value === $selected) {
                    $item['selected'] = true;
                }

                return $item;
            })
            ->toArray();
    }

    public static function iconableArray(string|int $selected = null): array
    {
        return collect(static::cases())
            ->map(function ($case) use ($selected) {
                $item = [
                    'value' => $case->value,
                    'label' => $case instanceof HasLabel ? $case->getLabel() : $case->name,
                ];

                if ($case instanceof HasIcon) {
                    $item['icon'] = $case->getIcon();
                }

                if ($case instanceof HasColor) {
                    $item['color'] = $case->getColor();
                }

                if ($selected !== null && $case->value === $selected) {
                    $item['selected'] = true;
                }

                return $item;
            })
            ->toArray();
    }
}