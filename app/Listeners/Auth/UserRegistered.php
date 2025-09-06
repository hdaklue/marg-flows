<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Actions\User\GenerateUserAvatar;
use App\Services\Avatar\AvatarService;
use Filament\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class UserRegistered implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $avatarUrl = AvatarService::buildAvatarUrl($event->getUser());
        GenerateUserAvatar::dispatch($avatarUrl, $event->getUser());
    }
}
