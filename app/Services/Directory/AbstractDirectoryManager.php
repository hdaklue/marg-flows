<?php

declare(strict_types=1);

namespace App\Services\Directory;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Abstract Directory Manager.
 *
 * Base class that enforces common patterns for directory management while allowing
 * concrete implementations to customize storage disk selection and base directory logic.
 * Provides shared utility methods for file operations, URL generation, and path building.
 */
abstract class AbstractDirectoryManager
{
    /**
     * Get all files for a specific identifier.
     *
     * @param  string|null  $identifier  The identifier (tenant ID, user ID, etc.)
     * @return array<string> Array of file paths within the directory
     */
    public function getAllFiles(?string $identifier = null): array
    {
        return Storage::disk($this->getDisk())->allFiles($this->getBaseDirectory($identifier));
    }

    /**
     * Get secure URL for a file requiring authentication.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(string $identifier, string $type, string $fileName): string
    {
        return route('file.serve', [
            'tenant' => $identifier,
            'type' => $type,
            'filename' => $fileName,
        ]);
    }

    /**
     * Get temporary URL for a file with expiration.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @param  int  $expiresIn  Expiration time in seconds (default 30 minutes)
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(string $identifier, string $type, string $fileName, int $expiresIn = 1800): string
    {
        $basePath = $this->getBaseDirectory($identifier);
        $path = "{$basePath}/{$type}/{$fileName}";

        return Storage::disk($this->getDisk())->temporaryUrl($path, now()->addSeconds($expiresIn));
    }

    /**
     * Delete a file from storage.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename to delete
     * @return bool True if deletion was successful
     */
    public function deleteFile(string $identifier, string $type, string $fileName): bool
    {
        $basePath = $this->getBaseDirectory($identifier);
        $path = "{$basePath}/{$type}/{$fileName}";

        return Storage::disk($this->getDisk())->delete($path);
    }

    /**
     * Get file contents as string.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function getFileContents(string $identifier, string $type, string $fileName): ?string
    {
        $basePath = $this->getBaseDirectory($identifier);
        $path = "{$basePath}/{$type}/{$fileName}";

        return Storage::disk($this->getDisk())->exists($path)
            ? Storage::disk($this->getDisk())->get($path)
            : null;
    }

    /**
     * Check if a file exists.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename to check
     * @return bool True if file exists
     */
    public function fileExists(string $identifier, string $type, string $fileName): bool
    {
        $basePath = $this->getBaseDirectory($identifier);
        $path = "{$basePath}/{$type}/{$fileName}";

        return Storage::disk($this->getDisk())->exists($path);
    }

    /**
     * Get file size in bytes.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename to check
     * @return int|null File size in bytes or null if not found
     */
    public function getFileSize(string $identifier, string $type, string $fileName): ?int
    {
        $basePath = $this->getBaseDirectory($identifier);
        $path = "{$basePath}/{$type}/{$fileName}";

        return Storage::disk($this->getDisk())->exists($path)
            ? Storage::disk($this->getDisk())->size($path)
            : null;
    }

    /**
     * Get absolute or storage-relative path for a file.
     *
     * Returns absolute filesystem path for local storage, or storage path for cloud storage.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename to get path for
     * @return string|null File path or null if not accessible
     */
    public function getFilePath(string $identifier, string $type, string $fileName): ?string
    {
        $basePath = $this->getBaseDirectory($identifier);
        $fullPath = "{$basePath}/{$type}/{$fileName}";

        $disk = Storage::disk($this->getDisk());

        // Only return path for local disks
        if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return $disk->path($fullPath);
        }

        // For cloud storage, return the storage path (not local file path)
        return $fullPath;
    }

    /**
     * Get the storage disk to use for operations.
     *
     * @return string The storage disk name
     */
    abstract protected function getDisk(): string;

    /**
     * Get the base directory path for storage operations.
     *
     * @param  string|null  $identifier  Optional identifier for directory customization
     * @return string Base directory path
     */
    abstract protected function getBaseDirectory(?string $identifier = null): string;

    /**
     * Build full file path with directory structure.
     *
     * Protected helper method for building consistent paths.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename
     * @return string Full path (baseDirectory/type/filename)
     */
    protected function buildFilePath(string $identifier, string $type, string $fileName): string
    {
        $basePath = $this->getBaseDirectory($identifier);

        return "{$basePath}/{$type}/{$fileName}";
    }

    /**
     * Ensure directory exists for operations.
     *
     * @param  string  $identifier  The identifier (tenant ID, etc.)
     * @param  string  $type  The file type directory
     */
    protected function ensureDirectoryExists(string $identifier, string $type): void
    {
        $basePath = $this->getBaseDirectory($identifier);
        $directory = "{$basePath}/{$type}";

        if (! Storage::disk($this->getDisk())->exists($directory)) {
            Storage::disk($this->getDisk())->makeDirectory($directory);
        }
    }
}
