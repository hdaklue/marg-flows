<?php

declare(strict_types=1);

namespace App\Concerns\Enums;

trait EnumSelectArrays
{
    public static function simpleArray(string|int|null $selected = null): array
    {
        return collect(static::cases())->map(function ($case) use ($selected) {
            $item = [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ];

            if ($selected !== null && $case->value === $selected) {
                $item['selected'] = true;
            }

            return $item;
        })->toArray();
    }

    public static function colorfulArray(string|int|null $selected = null): array {
        return collect(static::cases())->map(function ($case) use ($selected) {
            $item = [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
            ];

            if ($selected !== null && $case->value === $selected) {
                $item['selected'] = true;
            }

            return $item;
        })->toArray();
    }
}
