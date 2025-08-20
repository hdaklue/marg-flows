<?php

declare(strict_types=1);

namespace App\Services\Avatar;

use App\Actions\User\GenerateUserAvatar;
use App\Models\User;
use App\Services\Directory\DirectoryManager;
use Illuminate\Support\Uri;

final class AvatarService
{
    public static function generateAvatarUrl(User $user): string
    {
        if (! empty($user->getAvatarFileName())) {
            return DirectoryManager::avatars()->getFileUrl($user->getAvatarFileName());
        }

        // Use user's name for initials, fallback to email if no name
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

        $url = (string) Uri::of('https://ui-avatars.com/api/')
            ->withQuery($params);

        GenerateUserAvatar::dispatch($url, $user);

        return $url;
    }
}
