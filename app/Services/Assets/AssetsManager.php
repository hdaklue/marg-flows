<?php

declare(strict_types=1);

namespace App\Services\Assets;

use App\Services\Assets\Contracts\ChunksStorageStrategyContract;
use App\Services\Assets\Contracts\DocumentStorageStrategyContract;
use App\Services\Assets\Strategies\ChunksStorageStrategy;
use App\Services\Assets\Strategies\DocumentStorageStrategy;

final class AssetsManager
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