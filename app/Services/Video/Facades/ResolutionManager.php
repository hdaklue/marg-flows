<?php

declare(strict_types=1);

namespace App\Services\Video\Facades;

use App\Services\Video\Enums\NamingPattern;
use App\Services\Video\Services\ResolutionManager as ResolutionManagerService;

/**
 * @method static ResolutionManagerService from(string $sourcePath, string $disk = 'local', ?\App\Services\Video\Enums\NamingPattern $namingStrategy = null)
 * @method static ResolutionManagerService fromDisk(string $sourcePath, string $disk = 'local', ?\App\Services\Video\Enums\NamingPattern $namingStrategy = null)
 *
 * @see ResolutionManagerService
 */
final class ResolutionManager
{
    /**
     * Create a new ResolutionManager instance from path.
     */
    public static function from(
        string $sourcePath,
        string $disk = 'local',
        ?NamingPattern $namingStrategy = null,
    ): ResolutionManagerService {
        return ResolutionManagerService::from($sourcePath, $disk, $namingStrategy);
    }

    /**
     * Create a new ResolutionManager instance from disk.
     */
    public static function fromDisk(
        string $sourcePath,
        string $disk = 'local',
        ?NamingPattern $namingStrategy = null,
    ): ResolutionManagerService {
        return ResolutionManagerService::fromDisk($sourcePath, $disk, $namingStrategy);
    }

    /**
     * Forward static calls to the ResolutionManager service.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return ResolutionManagerService::$method(...$arguments);
    }
}
