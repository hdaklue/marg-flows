<?php

declare(strict_types=1);

namespace App\Services\Directory\Facades;

use App\Services\Directory\DirectoryManager as DirectoryManagerService;
use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static DocumentStorageStrategyContract document()
 * @method static ChunksStorageStrategyContract chunks()
 */
final class DirectoryManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DirectoryManagerService::class;
    }
}