<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

use App\Services\Directory\Strategies\VideoStorageStrategy;

/**
 * Document Directory Manager Contract.
 *
 * Defines the interface for managing tenant-specific document storage operations
 * including documents, chunks, videos, and associated utility methods.
 */
interface DocumentDirectoryManagerContract
{
    /**
     * Get chunks storage strategy for file upload sessions.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return ChunksStorageStrategyContract Configured chunks storage strategy
     */
    public function chunks(): ChunksStorageStrategyContract;

    /**
     * Get video storage strategy for a specific tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $baseDirectory  The base directory for video storage
     * @return VideoStorageStrategy Configured video storage strategy
     */
    public function video(string $baseDirectory): VideoStorageStrategy;

    /**
     * Get all files for a specific tenant.
     *
     * @param  string|null  $identifier  The tenant identifier
     * @return array<string> Array of file paths within the tenant directory
     */
    public function getAllFiles(?string $identifier = null): array;

    /**
     * Get secure URL for a file requiring authentication.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(string $identifier, string $type, string $fileName): string;

    /**
     * Get temporary URL for a file with expiration.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @param  int  $expiresIn  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(
        string $identifier,
        string $type,
        string $fileName,
        int $expiresIn = 1800,
    ): string;
}
