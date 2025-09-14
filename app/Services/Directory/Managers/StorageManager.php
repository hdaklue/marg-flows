<?php

declare(strict_types=1);

namespace App\Services\Directory\Managers;

use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use App\Services\Directory\Contracts\StorageManagerContract;
use App\Services\Directory\Contracts\StorageStrategyContract;
use App\Services\Directory\Strategies\AvatarStorageStrategy;
use App\Services\Directory\Strategies\ChunksStorageStrategy;
use App\Services\Directory\Strategies\DocumentStorageStrategy;
use App\Services\Directory\Strategies\TempStorageStrategy;
use App\Services\Directory\Strategies\VideoStorageStrategy;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;

/**
 * Storage Manager.
 *
 * Centralizes the creation and configuration of storage strategies.
 * Encapsulates the instantiation logic and dependency management
 * for all storage strategy types used throughout the application.
 */
final class StorageManager implements StorageManagerContract
{
    /**
     * Create document storage strategy for a specific tenant.
     *
     * @param  string  $basePath  The base path for the storage strategy
     * @return DocumentStorageStrategyContract Configured document storage strategy
     */
    public function createDocumentStrategy(string $basePath): DocumentStorageStrategyContract
    {
        return new DocumentStorageStrategy($basePath);
    }

    /**
     * Create chunks storage strategy for file upload sessions.
     *
     * @param  string  $basePath  The base path for the storage strategy
     * @return ChunksStorageStrategyContract Configured chunks storage strategy
     */
    public function createChunksStrategy(string $basePath): ChunksStorageStrategyContract
    {
        return new ChunksStorageStrategy($basePath);
    }

    /**
     * Create avatar storage strategy for user profile images.
     *
     * @return StorageStrategyContract Configured avatar storage strategy
     */
    public function createAvatarStrategy(): StorageStrategyContract
    {
        return new AvatarStorageStrategy;
    }

    /**
     * Create temporary storage strategy for transient files.
     *
     * @return StorageStrategyContract Configured temporary storage strategy
     */
    public function createTempStrategy(): StorageStrategyContract
    {
        return new TempStorageStrategy;
    }

    /**
     * Create video storage strategy with LaraPath configuration.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $baseDirectory  The base directory for video storage
     * @return VideoStorageStrategy Configured video storage strategy
     */
    public function createVideoStrategy(string $tenantId, string $baseDirectory): VideoStorageStrategy
    {
        $rootDirectory = LaraPath::base(
            $tenantId,
            SanitizationStrategy::HASHED,
        )->add($baseDirectory);

        return new VideoStorageStrategy($rootDirectory);
    }
}
