<?php

declare(strict_types=1);

namespace App\Services\FileServing\System;

use App\Contracts\File\HasFiles;
use App\Models\User;
use App\Services\Directory\Managers\SystemDirectoryManager;
use App\Services\FileServing\AbstractFileResolver;
use Illuminate\Support\Facades\Storage;

/**
 * System File Resolver.
 *
 * Handles file serving for system-wide files including user avatars
 * and temporary files. System files are considered public to authenticated
 * users, so validation always returns true for simplicity.
 */
final class SystemFileResolver extends AbstractFileResolver
{
    public function __construct(
        private readonly SystemDirectoryManager $directoryManager,
    ) {}

    /**
     * Create a static instance for dependency injection.
     */
    public static function make(): static
    {
        return new self(SystemDirectoryManager::instance());
    }

    /**
     * Check if file exists for system files.
     *
     * @param  HasFiles  $entity  The system entity
     * @param  string  $type  The file type (avatars, temp, etc.)
     * @param  string  $filename  The filename
     * @return bool True if file exists, false otherwise
     */
    public function fileExists(HasFiles $entity, string $type, string $filename): bool
    {
        $disk = config('directory-system.storage.disk', 'public');
        $basePath = $this->directoryManager->getBaseDirectory($type);
        $path = "{$basePath}/{$filename}";

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file size for system files.
     *
     * @param  HasFiles  $entity  The system entity
     * @param  string  $type  The file type (avatars, temp, etc.)
     * @param  string  $filename  The filename
     * @return int|null File size in bytes, null if file doesn't exist
     */
    public function getFileSize(HasFiles $entity, string $type, string $filename): ?int
    {
        if (! $this->fileExists($entity, $type, $filename)) {
            return null;
        }

        $disk = config('directory-system.storage.disk', 'public');
        $basePath = $this->directoryManager->getBaseDirectory($type);
        $path = "{$basePath}/{$filename}";

        return Storage::disk($disk)->size($path);
    }

    /**
     * Get avatar URL for a user.
     *
     * @param  User  $user  The user
     * @return string|null Avatar URL or null if no avatar
     */
    public function getUserAvatarUrl(User $user): ?string
    {
        if (empty($user->getAvatarFileName())) {
            return null;
        }

        return $this->directoryManager->avatars()->getFileUrl($user->getAvatarFileName());
    }

    /**
     * Get avatar path for a user.
     *
     * @param  User  $user  The user
     * @return string|null Avatar path or null if no avatar
     */
    public function getUserAvatarPath(User $user): ?string
    {
        if (empty($user->getAvatarFileName())) {
            return null;
        }

        return $this->directoryManager->avatars()->getRelativePath($user->getAvatarFileName());
    }

    /**
     * Clean up expired temporary files.
     *
     * @return int Number of files cleaned up
     */
    public function cleanupTempFiles(): int
    {
        return $this->directoryManager->cleanupTempFiles();
    }

    /**
     * Validate access for system files.
     *
     * System files (avatars, temp files) are accessible to any authenticated user,
     * so this always returns true for simplicity.
     *
     * @param  HasFiles  $entity  The system entity (User, etc.)
     * @param  User  $user  The user requesting access
     * @return bool Always returns true for system files
     */
    protected function validateAccess(HasFiles $entity, User $user): bool
    {
        // System files are accessible to any authenticated user
        return true;
    }

    /**
     * Generate secure URL for system files.
     *
     * @param  HasFiles  $entity  The system entity
     * @param  string  $type  The file type (avatars, temp, etc.)
     * @param  string  $filename  The filename
     * @return string Secure URL requiring authentication
     */
    protected function generateSecureUrl(HasFiles $entity, string $type, string $filename): string
    {
        return route('system.files.serve', [
            'type' => $type,
            'filename' => $filename,
        ]);
    }

    /**
     * Generate temporary URL for system files.
     *
     * @param  HasFiles  $entity  The system entity
     * @param  string  $type  The file type (avatars, temp, etc.)
     * @param  string  $filename  The filename
     * @param  int|null  $expires  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    protected function generateTemporaryUrl(HasFiles $entity, string $type, string $filename, ?int $expires = null): string
    {
        $expires ??= config('directory-system.public_access.default_expiry', 3600);

        return $this->directoryManager->getTemporaryUrl($type, $type, $filename, $expires);
    }

    /**
     * Perform the actual file deletion for system files.
     *
     * @param  HasFiles  $entity  The system entity
     * @param  string  $type  The file type (avatars, temp, etc.)
     * @param  string  $filename  The filename
     * @return bool True if deletion was successful, false otherwise
     */
    protected function performFileDelete(HasFiles $entity, string $type, string $filename): bool
    {
        if (! $this->fileExists($entity, $type, $filename)) {
            return false;
        }

        $disk = config('directory-system.storage.disk', 'public');
        $basePath = $this->directoryManager->getBaseDirectory($type);
        $path = "{$basePath}/{$filename}";

        return Storage::disk($disk)->delete($path);
    }
}
