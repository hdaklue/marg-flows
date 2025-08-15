<?php

declare(strict_types=1);

namespace App\Services\Directory;

use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use App\Services\Directory\Strategies\ChunksStorageStrategy;
use App\Services\Directory\Strategies\DocumentStorageStrategy;

final class DirectoryManager
{
    public static function document(): DocumentStorageStrategyContract
    {
        return new DocumentStorageStrategy();
    }

    public static function chunks(): ChunksStorageStrategyContract
    {
        return new ChunksStorageStrategy();
    }
}