<?php

declare(strict_types=1);

namespace App\Services\Video\Facades;

use App\Services\Video\Services\ResolutionManager as ResolutionManagerService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ResolutionManagerService from(string $sourcePath, string $disk = 'local', ?\App\Services\Video\Enums\NamingPattern $namingStrategy = null)
 * @method static ResolutionManagerService fromDisk(string $sourcePath, string $disk = 'local', ?\App\Services\Video\Enums\NamingPattern $namingStrategy = null)
 *
 * @see ResolutionManagerService
 */
final class ResolutionManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ResolutionManagerService::class;
    }
}
