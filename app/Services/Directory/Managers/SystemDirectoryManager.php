<?php

declare(strict_types=1);

namespace App\Services\Directory\Managers;

use App\Services\Directory\AbstractDirectoryManager;
use App\Services\Directory\Contracts\StorageStrategyContract;
use App\Services\Directory\Contracts\SystemDirectoryManagerContract;
use App\Services\Directory\Strategies\AvatarStorageStrategy;
use App\Services\Directory\Strategies\TempStorageStrategy;
use Illuminate\Support\Facades\Storage;

/**
 * System Directory Manager.
 *
 * Self-contained system storage manager that uses directory-system configuration.
 * Handles system-wide storage operations that are not tenant-specific, such as
 * avatars and temporary files. Provides centralized management for all system-level
 * file operations with independent configuration.
 */
final class SystemDirectoryManager extends AbstractDirectoryManager implements
    SystemDirectoryManagerContract
{
    /**
     * Create a static instance for system operations.
     *
     * @return static Configured instance for system operations
     */
    public static function instance(): static
    {
        return new self();
    }

    /**
     * Get avatar storage strategy for user profile images.
     *
     * Avatar storage is system-wide and not tenant-specific.
     *
     * @return StorageStrategyContract Configured avatar storage strategy
     */
    public function avatars(): StorageStrategyContract
    {
        return new AvatarStorageStrategy();
    }

    /**
     * Get temporary storage strategy for transient files.
     *
     * @return StorageStrategyContract Configured temporary storage strategy
     */
    public function temp(): StorageStrategyContract
    {
        return new TempStorageStrategy();
    }

    /**
     * Get all system files in a specific category.
     *
     * @param  string|null  $identifier  The file category (avatars, temp, etc.)
     * @return array<string> Array of file paths within the category directory
     */
    public function getAllFiles(null|string $identifier = null): array
    {
        return parent::getAllFiles($identifier);
    }

    /**
     * Get temporary URL for a system file with expiration.
     *
     * @param  string  $category  The file category
     * @param  string  $type  The file type directory
     * @param  string  $fileName  The filename
     * @param  int|null  $expiresIn  Expiration time in seconds (uses config default if null)
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(
        string $category,
        string $type,
        string $fileName,
        null|int $expiresIn = null,
    ): string {
        $expiresIn ??= config('directory-system.public_access.default_expiry', 3600);

        return parent::getTemporaryUrl($category, $type, $fileName, $expiresIn);
    }

    /**
     * Clean up expired temporary files.
     *
     * @return int Number of files cleaned up
     */
    public function cleanupTempFiles(): int
    {
        if (!config('directory-system.temp.auto_cleanup', true)) {
            return 0;
        }

        $tempPath = $this->getBaseDirectory('temp');
        $maxAge = config('directory-system.temp.max_age', 86400); // 24 hours
        $disk = $this->getDisk();

        $files = collect(Storage::disk($disk)->allFiles($tempPath));
        $cutoff = now()->subSeconds($maxAge);
        $cleaned = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);

            if ($lastModified && $lastModified < $cutoff->timestamp) {
                Storage::disk($disk)->delete($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get avatar directory path.
     *
     * @return string Avatar directory path
     */
    public function getAvatarDirectory(): string
    {
        return $this->getBaseDirectory('avatars');
    }

    /**
     * Get temporary files directory path.
     *
     * @return string Temporary files directory path
     */
    public function getTempDirectory(): string
    {
        return $this->getBaseDirectory('temp');
    }

    /**
     * Get the storage disk to use for system operations.
     *
     * Uses the directory-system configuration.
     *
     * @return string The storage disk name
     */
    public function getDisk(): string
    {
        return config('directory-system.storage.disk', 'public');
    }

    /**
     * Get the base directory path for system storage operations.
     *
     * Uses the configured base path with category-specific subdirectories.
     *
     * @param  string|null  $identifier  Optional identifier for directory customization
     * @return string Base directory path
     */
    protected function getBaseDirectory(null|string $identifier = null): string
    {
        $basePath = config('directory-system.storage.base_path', 'system');

        if ($identifier === null) {
            return $basePath;
        }

        // Handle specific system categories
        return match ($identifier) {
            'avatars' => $basePath . '/' . config('directory-system.avatars.directory', 'avatars'),
            'temp' => $basePath . '/' . config('directory-system.temp.directory', 'temp'),
            default => "{$basePath}/{$identifier}",
        };
    }
}
