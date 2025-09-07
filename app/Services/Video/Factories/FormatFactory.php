<?php

declare(strict_types=1);

namespace App\Services\Video\Factories;

use App\Services\Video\Contracts\VideoFormatContract;
use InvalidArgumentException;

final class FormatFactory
{
    private static array $instances = [];

    /**
     * Create or get existing format instance.
     */
    public static function create(string $formatClass): VideoFormatContract
    {
        if (!class_exists($formatClass)) {
            throw new InvalidArgumentException(
                "Format class does not exist: {$formatClass}",
            );
        }

        if (!is_subclass_of($formatClass, VideoFormatContract::class)) {
            throw new InvalidArgumentException(
                "Format class must implement VideoFormatContract: {$formatClass}",
            );
        }

        if (!isset(self::$instances[$formatClass])) {
            self::$instances[$formatClass] = $formatClass::createInstance();
        }

        return self::$instances[$formatClass];
    }

    /**
     * Clear all cached instances (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }

    /**
     * Get all cached format instances.
     */
    public static function getCachedInstances(): array
    {
        return self::$instances;
    }
}
