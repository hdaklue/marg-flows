<?php

declare(strict_types=1);

namespace App\Services\Assets\Facades;

use App\Services\Assets\AssetsManager as AssetsManagerService;
use App\Services\Assets\Contracts\ChunksStorageStrategyContract;
use App\Services\Assets\Contracts\DocumentStorageStrategyContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static DocumentStorageStrategyContract document()
 * @method static ChunksStorageStrategyContract chunks()
 */
final class AssetsManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AssetsManagerService::class;
    }
}