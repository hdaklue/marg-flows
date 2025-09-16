<?php

declare(strict_types=1);

namespace App\Services\Directory\Managers;

use App\Services\Directory\AbstractDirectoryManager;
use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Strategies\ChunksStorageStrategy;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Chunks Directory Manager.
 *
 * Self-contained chunk storage manager that uses directory-chunks configuration.
 * Handles chunk-based file upload storage operations including session management,
 * tenant isolation, and chunk processing. Provides centralized management for all
 * chunk-related file operations with independent configuration.
 */
final class ChunksDirectoryManager extends AbstractDirectoryManager
{
    /**
     * Create a static instance for tenant operations.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return static Configured instance for the tenant
     */
    public static function forTenant(string $tenantId): static
    {
        return new self;
    }

    /**
     * Get chunks storage strategy for file upload sessions.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return ChunksStorageStrategyContract Configured chunks storage strategy
     */
    public function chunks(string $tenantId): ChunksStorageStrategyContract
    {
        $basePath = $this->getBaseDirectory($tenantId);

        return new ChunksStorageStrategy($basePath);
    }

    /**
     * Get all chunk files for a specific tenant.
     *
     * @param  string|null  $identifier  The tenant identifier
     * @return array<string> Array of file paths within the tenant chunk directory
     */
    public function getAllFiles(?string $identifier = null): array
    {
        return parent::getAllFiles($identifier);
    }

    /**
     * Clean up expired chunk sessions.
     *
     * @return int Number of chunk sessions cleaned up
     */
    public function cleanupExpiredSessions(): int
    {
        if (! config('directory-chunks.cleanup.auto_cleanup', true)) {
            return 0;
        }

        $basePath = config('directory-chunks.storage.base_path', 'chunks');
        $sessionTtl = config('directory-chunks.session_ttl', 3600);
        $disk = $this->getDisk();

        $directories = Storage::disk($disk)->directories($basePath);
        $cutoff = now()->subSeconds($sessionTtl);
        $cleaned = 0;

        foreach ($directories as $directory) {
            $lastModified = Storage::disk($disk)->lastModified($directory);

            if ($lastModified && $lastModified < $cutoff->timestamp) {
                Storage::disk($disk)->deleteDirectory($directory);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Clean up failed chunks for a specific tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return int Number of failed chunks cleaned up
     */
    public function cleanupFailedChunks(string $tenantId): int
    {
        $tenantPath = $this->getBaseDirectory($tenantId);
        $failedRetention = config('directory-chunks.cleanup.failed_chunks_retention', 1800);
        $disk = $this->getDisk();

        $files = collect(Storage::disk($disk)->allFiles($tenantPath));
        $cutoff = now()->subSeconds($failedRetention);
        $cleaned = 0;

        foreach ($files as $file) {
            // Check if it's a failed chunk file (you might want to add specific logic here)
            $lastModified = Storage::disk($disk)->lastModified($file);

            if ($lastModified && $lastModified < $cutoff->timestamp) {
                Storage::disk($disk)->delete($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get chunk session directory for a tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sessionId  The chunk session identifier
     * @return string Chunk session directory path
     */
    public function getSessionDirectory(string $tenantId, string $sessionId): string
    {
        $basePath = $this->getBaseDirectory($tenantId);
        $sessionPrefix = config('directory-chunks.tenant_isolation.session_prefix', 'chunk_');

        return "{$basePath}/{$sessionPrefix}{$sessionId}";
    }

    /**
     * Get chunk storage statistics.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return array<string, mixed> Statistics about chunk storage usage
     */
    public function getStorageStats(string $tenantId): array
    {
        $tenantPath = $this->getBaseDirectory($tenantId);
        $disk = $this->getDisk();

        if (! Storage::disk($disk)->exists($tenantPath)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'active_sessions' => 0,
                'oldest_chunk' => null,
                'newest_chunk' => null,
            ];
        }

        $files = Storage::disk($disk)->allFiles($tenantPath);
        $totalSize = 0;
        $oldest = null;
        $newest = null;

        foreach ($files as $file) {
            $size = Storage::disk($disk)->size($file);
            $totalSize += $size;

            $lastModified = Storage::disk($disk)->lastModified($file);
            if ($oldest === null || $lastModified < $oldest) {
                $oldest = $lastModified;
            }
            if ($newest === null || $lastModified > $newest) {
                $newest = $lastModified;
            }
        }

        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'active_sessions' => count(Storage::disk($disk)->directories($tenantPath)),
            'oldest_chunk' => $oldest ? date('Y-m-d H:i:s', $oldest) : null,
            'newest_chunk' => $newest ? date('Y-m-d H:i:s', $newest) : null,
        ];
    }

    /**
     * Get the base directory path for tenant-specific chunk storage operations.
     *
     * Uses the configured hash strategy and base path for tenant isolation.
     *
     * @param  string|null  $identifier  The tenant identifier
     * @return string Base directory path (hashed tenant ID with base path)
     */
    public function getBaseDirectory(?string $identifier = null): string
    {
        if ($identifier === null) {
            throw new InvalidArgumentException('Tenant identifier is required for chunk storage operations.');
        }

        $basePath = config('directory-chunks.storage.base_path', 'chunks');
        $hashedId = LaraPath::base($identifier, SanitizationStrategy::HASHED);

        return $basePath ? "{$basePath}/{$hashedId}" : (string) $hashedId;
    }

    /**
     * Get the storage disk to use for chunk operations.
     *
     * Uses the directory-chunks configuration.
     *
     * @return string The storage disk name
     */
    protected function getDisk(): string
    {
        return config('directory-chunks.storage.disk', 'local');
    }
}
