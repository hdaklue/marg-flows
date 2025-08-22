<?php

declare(strict_types=1);

namespace App\Concerns\User;

use App\Services\Avatar\AvatarService;

trait ManagesAvatar
{
    public function getFilamentAvatarUrl(): ?string
    {
        return AvatarService::generateAvatarUrl($this);
    }

    public function getAvatarUrl(): string
    {
        return AvatarService::generateAvatarUrl($this);
    }

    public function getAvatarPath()
    {
        return AvatarService::getAvatarPath($this);
    }

    public function getAvatarFileName(): ?string
    {
        return $this->avatar;
    }
}