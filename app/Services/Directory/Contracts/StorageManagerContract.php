<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

use App\Services\Directory\Strategies\VideoStorageStrategy;

/**
 * Storage Manager Contract.
 *
 * Defines the interface for storage strategy creation and management.
 * Centralizes the instantiation of storage strategies with proper configuration.
 */
interface StorageManagerContract
{
    /**
     * Create document storage strategy for a specific tenant.
     *
     * @param  string  $basePath  The base path for the storage strategy
     * @return DocumentStorageStrategyContract Configured document storage strategy
     */
    public function createDocumentStrategy(string $basePath): DocumentStorageStrategyContract;

    /**
     * Create chunks storage strategy for file upload sessions.
     *
     * @param  string  $basePath  The base path for the storage strategy
     * @return ChunksStorageStrategyContract Configured chunks storage strategy
     */
    public function createChunksStrategy(string $basePath): ChunksStorageStrategyContract;

    /**
     * Create avatar storage strategy for user profile images.
     *
     * @return StorageStrategyContract Configured avatar storage strategy
     */
    public function createAvatarStrategy(): StorageStrategyContract;

    /**
     * Create temporary storage strategy for transient files.
     *
     * @return StorageStrategyContract Configured temporary storage strategy
     */
    public function createTempStrategy(): StorageStrategyContract;

    /**
     * Create video storage strategy with LaraPath configuration.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $baseDirectory  The base directory for video storage
     * @return VideoStorageStrategy Configured video storage strategy
     */
    public function createVideoStrategy(
        string $tenantId,
        string $baseDirectory,
    ): VideoStorageStrategy;
}
