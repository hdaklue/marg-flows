<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Generic Storage Strategy Contract.
 *
 * Defines the common interface for all storage strategies in the application.
 * Provides standard file operations, URL generation, and content access methods.
 */
interface StorageStrategyContract
{
    /**
     * Store an uploaded file.
     *
     * @param  UploadedFile  $file  The file to store
     * @return string The stored file identifier/path
     */
    public function store(UploadedFile $file): string;

    /**
     * Get the base URL or identifier for this storage strategy.
     *
     * @return string Base URL or strategy identifier
     */
    public function getUrl(): string;

    /**
     * Get the directory path for this storage strategy.
     *
     * @return string Directory path relative to storage root
     */
    public function getDirectory(): string;

    /**
     * Delete a file from storage.
     *
     * @param  string  $fileName  The filename to delete
     * @return bool True if deletion was successful
     */
    public function delete(string $fileName): bool;

    /**
     * Get storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get relative path for
     * @return string Storage-relative path (directory/filename)
     */
    public function getRelativePath(string $fileName): string;

    /**
     * Get file contents as string.
     *
     * @param  string  $fileName  The filename to retrieve
     * @return string|null File contents or null if not found
     */
    public function get(string $fileName): ?string;

    /**
     * Get absolute or storage-relative path for a file.
     *
     * @param  string  $fileName  The filename to get path for
     * @return string|null File path or null if not accessible
     */
    public function getPath(string $fileName): ?string;

    /**
     * Get public URL for accessing a file.
     *
     * @param  string  $fileName  The filename to get URL for
     * @return string Public URL for file access
     */
    public function getFileUrl(string $fileName): string;
}
