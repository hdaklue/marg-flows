<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Avatar\AvatarService;

if (!function_exists('avatarUrlFromUser')) {
    function avatarUrlFromUser(User $user): string
    {
        return AvatarService::generateAvatarUrl($user);
    }
}
