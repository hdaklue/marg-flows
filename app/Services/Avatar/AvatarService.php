<?php

declare(strict_types=1);

namespace App\Services\Avatar;

use App\Actions\User\GenerateUserAvatar;
use App\Models\User;
use App\Services\Directory\DirectoryManager;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Uri;

/**
 * Avatar Service.
 *
 * Handles avatar generation, file management, and URL generation for user profile images.
 * Provides fallback to UI Avatars service for users without uploaded avatars.
 */
final class AvatarService
{
    /**
     * Generate avatar URL for a user.
     *
     * Returns the stored avatar URL if user has uploaded one, otherwise generates
     * a fallback avatar using UI Avatars service with user initials.
     *
     * @param  User  $user  The user to generate avatar for
     * @return string Avatar URL (either stored file or generated fallback)
     */
    public static function generateAvatarUrl(User $user): string
    {
        if (! empty($user->getAvatarFileName())) {
            return DirectoryManager::avatars()->getFileUrl($user->getAvatarFileName());
        }

        // Use user's name for initials, fallback to email if no name
        $url = self::buildAvatarUrl($user);

        GenerateUserAvatar::dispatch($url, $user);

        return $url;
    }

    public static function buildAvatarUrl(User|Authenticatable $user): string
    {
        $name = $user->name ?: $user->email;

        $params = [
            'name' => $name,
            'size' => 64,
            'background' => '000000', // Dynamic color based on user
            'color' => 'ffffff',              // White text for contrast
            'format' => 'svg',
            'uppercase' => 'true',
            'length' => 2,
        ];

        return (string) Uri::of('https://ui-avatars.com/api/')
            ->withQuery($params);
    }

    /**
     * Generate standardized filename for user avatar.
     *
     * Uses MD5 hash of user key to ensure unique, collision-safe filenames
     * that replace previous avatars automatically.
     *
     * @param  User  $user  The user to generate filename for
     * @return string MD5 hashed filename (without extension)
     */
    public static function generateFileName(User $user): string
    {
        return md5($user->getKey());
    }

    /**
     * Get storage path for user's avatar file.
     *
     * Returns the relative storage path if user has an avatar, null otherwise.
     * Used by Filament FileUpload component for form population.
     *
     * @param  User  $user  The user to get avatar path for
     * @return string|null Relative storage path or null if no avatar
     */
    public static function getAvatarPath(User $user): ?string
    {
        if (! empty($user->getAvatarFileName())) {
            return DirectoryManager::avatars()->getRelativePath($user->getAvatarFileName());
        }

        return null;
    }
}
