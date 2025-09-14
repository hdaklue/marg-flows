<?php

declare(strict_types=1);

namespace App\Services\FileServing\Chunks;

use App\Contracts\File\HasFiles;
use App\Models\User;
use App\Services\Directory\Managers\ChunksDirectoryManager;
use App\Services\FileServing\AbstractFileResolver;
use Illuminate\Support\Facades\Storage;

/**
 * Chunk File Resolver.
 *
 * Handles file serving for upload chunk files and session management.
 * Chunk files are internal system files used for file upload processing,
 * so validation always returns true for simplicity.
 */
final class ChunkFileResolver extends AbstractFileResolver
{
    public function __construct(
        private readonly ChunksDirectoryManager $directoryManager,
    ) {}

    /**
     * Create a static instance for dependency injection.
     */
    public static function make(): static
    {
        return new self(ChunksDirectoryManager::forTenant(''));
    }

    /**
     * Check if file exists for chunk files.
     *
     * @param  HasFiles  $entity  The chunk entity
     * @param  string  $type  The file type (chunks, sessions, etc.)
     * @param  string  $filename  The filename
     * @return bool True if file exists, false otherwise
     */
    public function fileExists(HasFiles $entity, string $type, string $filename): bool
    {
        $disk = config('directory-chunks.storage.disk', 'local');
        $basePath = $this->directoryManager->getBaseDirectory($entity->getFileStorageIdentifier());
        $path = "{$basePath}/{$type}/{$filename}";

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file size for chunk files.
     *
     * @param  HasFiles  $entity  The chunk entity
     * @param  string  $type  The file type (chunks, sessions, etc.)
     * @param  string  $filename  The filename
     * @return int|null File size in bytes, null if file doesn't exist
     */
    public function getFileSize(HasFiles $entity, string $type, string $filename): ?int
    {
        if (! $this->fileExists($entity, $type, $filename)) {
            return null;
        }

        $disk = config('directory-chunks.storage.disk', 'local');
        $basePath = $this->directoryManager->getBaseDirectory($entity->getFileStorageIdentifier());
        $path = "{$basePath}/{$type}/{$filename}";

        return Storage::disk($disk)->size($path);
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
        return $this->directoryManager->getSessionDirectory($tenantId, $sessionId);
    }

    /**
     * Get chunk storage statistics for a tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return array<string, mixed> Statistics about chunk storage usage
     */
    public function getStorageStats(string $tenantId): array
    {
        return $this->directoryManager->getStorageStats($tenantId);
    }

    /**
     * Clean up expired chunk sessions.
     *
     * @return int Number of chunk sessions cleaned up
     */
    public function cleanupExpiredSessions(): int
    {
        return $this->directoryManager->cleanupExpiredSessions();
    }

    /**
     * Clean up failed chunks for a specific tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return int Number of failed chunks cleaned up
     */
    public function cleanupFailedChunks(string $tenantId): int
    {
        return $this->directoryManager->cleanupFailedChunks($tenantId);
    }

    /**
     * Validate access for chunk files.
     *
     * Chunk files are internal system files used for upload processing,
     * so this always returns true for simplicity.
     *
     * @param  HasFiles  $entity  The chunk entity
     * @param  User  $user  The user requesting access
     * @return bool Always returns true for chunk files
     */
    protected function validateAccess(HasFiles $entity, User $user): bool
    {
        // Chunk files are internal system files, always accessible
        return true;
    }

    /**
     * Generate secure URL for chunk files.
     *
     * @param  HasFiles  $entity  The chunk entity
     * @param  string  $type  The file type (chunks, sessions, etc.)
     * @param  string  $filename  The filename
     * @return string Secure URL requiring authentication
     */
    protected function generateSecureUrl(HasFiles $entity, string $type, string $filename): string
    {
        return route('chunks.files.serve', [
            'identifier' => $entity->getFileStorageIdentifier(),
            'type' => $type,
            'filename' => $filename,
        ]);
    }

    /**
     * Generate temporary URL for chunk files.
     *
     * @param  HasFiles  $entity  The chunk entity
     * @param  string  $type  The file type (chunks, sessions, etc.)
     * @param  string  $filename  The filename
     * @param  int|null  $expires  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    protected function generateTemporaryUrl(HasFiles $entity, string $type, string $filename, ?int $expires = null): string
    {
        $expires ??= config('directory-chunks.session_ttl', 3600);

        $disk = config('directory-chunks.storage.disk', 'local');
        $basePath = $this->directoryManager->getBaseDirectory($entity->getFileStorageIdentifier());
        $path = "{$basePath}/{$type}/{$filename}";

        return Storage::disk($disk)->temporaryUrl($path, now()->addSeconds($expires));
    }

    /**
     * Perform the actual file deletion for chunk files.
     *
     * @param  HasFiles  $entity  The chunk entity
     * @param  string  $type  The file type (chunks, sessions, etc.)
     * @param  string  $filename  The filename
     * @return bool True if deletion was successful, false otherwise
     */
    protected function performFileDelete(HasFiles $entity, string $type, string $filename): bool
    {
        if (! $this->fileExists($entity, $type, $filename)) {
            return false;
        }

        $disk = config('directory-chunks.storage.disk', 'local');
        $basePath = $this->directoryManager->getBaseDirectory($entity->getFileStorageIdentifier());
        $path = "{$basePath}/{$type}/{$filename}";

        return Storage::disk($disk)->delete($path);
    }
}
