<?php

declare(strict_types=1);

namespace App\Services\Directory\Utils\Strategies;

use App\Services\Directory\Utils\Contracts\SanitizationStrategyContract;

/**
 * Snake case sanitization strategy.
 * 
 * Converts input strings to snake_case format.
 * Useful for creating filesystem-safe names that maintain readability.
 */
final class SnakeStrategy implements SanitizationStrategyContract
{
    /**
     * Apply snake_case sanitization to the input string.
     *
     * @param string $input Input string to convert
     * @return string Snake_case string
     */
    public static function apply(string $input): string
    {
        return str($input)->snake()->toString();
    }
}