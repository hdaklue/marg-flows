<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\StorageStrategyContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Base Storage Strategy
 *
 * Abstract base class providing common file storage operations for all storage strategies.
 * Implements the StorageStrategyContract with shared functionality while allowing
 * concrete strategies to customize specific behaviors.
 */
abstract class BaseStorageStrategy implements StorageStrategyContract
{
    /**
     * Store uploaded file in the strategy's directory.
     *
     * @param UploadedFile $file The file to store
     * @return string The stored file identifier/path
     */
    abstract public function store(UploadedFile $file): string;

    /**
     * Get the base URL for this storage strategy.
     *
     * @return string Base URL or identifier for the storage type
     */
    abstract public function getUrl(): string;

    /**
     * Get the directory path for this storage strategy.
     *
     * @return string Directory path relative to storage root
     */
    abstract public function getDirectory(): string;

    /**
     * Delete a file from storage.
     *
     * @param string $fileName The filename to delete
     * @return bool True if deletion was successful
     */
    public function delete(string $fileName): bool
    {
        return Storage::delete($this->getDirectory() . "/{$fileName}");
    }

    /**
     * Get file contents as string.
     *
     * @param string $fileName The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function get(string $fileName): null|string
    {
        return Storage::get($this->getDirectory() . "/{$fileName}");
    }

    /**
     * Get absolute or storage-relative path for a file.
     *
     * Returns absolute filesystem path for local storage, or storage path for cloud storage.
     *
     * @param string $fileName The filename to get path for
     * @return string|null File path or null if not accessible
     */
    public function getPath(string $fileName): null|string
    {
        $fullPath = $this->getDirectory() . "/{$fileName}";

        // Only return path for local disks
        if (Storage::getDefaultDriver() === 'local') {
            return Storage::path($fullPath);
        }

        // For cloud storage, return the storage path (not local file path)
        return $fullPath;
    }

    /**
     * Get public URL for accessing a file.
     *
     * @param string $fileName The filename to get URL for
     * @return string Public URL for file access
     */
    public function getFileUrl(string $fileName): string
    {
        return Storage::url($this->getDirectory() . "/{$fileName}");
    }

    /**
     * Get storage-relative path for a file.
     *
     * Used by form components and file management systems that need
     * storage paths relative to the disk root.
     *
     * @param string $fileName The filename to get relative path for
     * @return string Storage-relative path (directory/filename)
     */
    public function getRelativePath(string $fileName): string
    {
        return $this->getDirectory() . "/{$fileName}";
    }

    /**
     * Extract filename from a relative storage path.
     *
     * Utility method to get just the filename portion from a path like "directory/filename.ext".
     *
     * @param string $path The relative storage path
     * @return string Just the filename portion
     */
    public function getFileNameFromRelativePath(string $path): string
    {
        return Str::of($path)->afterLast('/')->toString();
    }

    /**
     * Build full file path with directory.
     *
     * Protected helper method for strategies with complex directory structures.
     *
     * @param string $fileName The filename to build path for
     * @return string Full path (directory/filename)
     */
    protected function buildFilePath(string $fileName): string
    {
        return $this->getDirectory() . "/{$fileName}";
    }
}
