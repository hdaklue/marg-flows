<?php

declare(strict_types=1);

namespace App\Services\Directory;

use App\Models\Tenant;
use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use App\Services\Directory\Contracts\StorageStrategyContract;
use App\Services\Directory\Strategies\AvatarStorageStrategy;
use App\Services\Directory\Strategies\ChunksStorageStrategy;
use App\Services\Directory\Strategies\DocumentStorageStrategy;
use App\Services\Directory\Strategies\TempStorageStrategy;
use App\Services\Directory\Strategies\VideoStorageStrategy;
use App\Services\Directory\Utils\Enums\SanitizationStrategy;
use App\Services\Directory\Utils\PathBuilder;
use Storage;

/**
 * Directory Manager Facade.
 *
 * Provides centralized access to storage strategies for different file types
 * in a multi-tenant environment. Handles documents, chunks, avatars, and temporary files.
 */
final class DirectoryManager
{
    /**
     * Get document storage strategy for a specific tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return DocumentStorageStrategyContract Configured document storage strategy
     */
    public static function document(string $tenantId): DocumentStorageStrategyContract
    {
        return new DocumentStorageStrategy(self::baseDirectiry($tenantId));
    }

    /**
     * Get chunks storage strategy for file upload sessions.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return ChunksStorageStrategyContract Configured chunks storage strategy
     */
    public static function chunks(string $tenantId): ChunksStorageStrategyContract
    {
        return new ChunksStorageStrategy(self::baseDirectiry($tenantId));
    }

    /**
     * Get avatar storage strategy for user profile images.
     *
     * Avatar storage is system-wide and not tenant-specific.
     *
     * @return StorageStrategyContract Configured avatar storage strategy
     */
    public static function avatars(): StorageStrategyContract
    {
        return new AvatarStorageStrategy;
    }

    /**
     * Get temporary storage strategy for transient files.
     *
     * @return StorageStrategyContract Configured temporary storage strategy
     */
    public static function temp(): StorageStrategyContract
    {
        return new TempStorageStrategy;
    }

    public static function video(string $tenantId, string $baseDirectory): VideoStorageStrategy
    {
        // $rootDirectory = self::baseDirectiry($tenantId);

        $rootDirectory = PathBuilder::base($tenantId, SanitizationStrategy::HASHED)->add($baseDirectory);

        return new VideoStorageStrategy($rootDirectory);
    }

    /**
     * Get base directory path for a tenant.
     *
     * Uses MD5 hash of tenant ID for security and consistency.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return string Base directory path (MD5 hashed tenant ID)
     */
    public static function baseDirectiry(string $tenantId): string
    {
        return md5($tenantId);
    }

    /**
     * Get all files for a specific tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return array<string> Array of file paths within the tenant directory
     */
    public static function getAllFiles(string $tenantId): array
    {
        return Storage::allFiles(self::baseDirectiry($tenantId));
    }
}
