<?php

declare(strict_types=1);

namespace App\Services\Directory;

use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use App\Services\Directory\Contracts\StorageStrategyContract;
use App\Services\Directory\Strategies\AvatarStorageStrategy;
use App\Services\Directory\Strategies\ChunksStorageStrategy;
use App\Services\Directory\Strategies\DocumentStorageStrategy;
use App\Services\Directory\Strategies\TempStorageStrategy;
use Storage;

final class DirectoryManager
{
    public static function document(string $tenantId): DocumentStorageStrategyContract
    {
        return new DocumentStorageStrategy($tenantId);
    }

    public static function chunks(string $tenantId): ChunksStorageStrategyContract
    {
        return new ChunksStorageStrategy($tenantId);
    }

    public static function avatars(): StorageStrategyContract
    {
        return new AvatarStorageStrategy;
    }

    public static function temp(): StorageStrategyContract
    {
        return new TempStorageStrategy;
    }

    public static function baseDirectiry(string $tenantId): string
    {
        return $tenantId;
    }

    public static function getAllFiles(string $tenantId)
    {
        return Storage::allFiles(self::baseDirectiry($tenantId));
    }
}
