<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\User\UserDto;
use App\Models\User;
use App\Services\Avatar\AvatarService;
use App\Services\Directory\Managers\SystemDirectoryManager;
use Lorisleiva\Actions\Concerns\AsAction;

final class UpdateBasicInfo
{
    use AsAction;

    public function handle(UserDto $dto, User $user): User
    {
        dd($dto);
        // Get current user attributes (normalize avatar to filename only)
        $currentData = $user->only(['name', 'timezone']);
        $currentData['avatar'] = $user->getAvatarFileName();

        // Get new data from DTO (normalize avatar to filename only)
        $newData = collect($dto->toArray())->except('id')->toArray();
        $newData['avatar'] = SystemDirectoryManager::instance()->avatars()->getFileNameFromRelativePath(
            $newData['avatar'],
        );

        // Check if avatar file changed and handle it specially
        if ($this->avatarChanged($user, $newData['avatar'])) {
            $newData['avatar'] = $this->handleAvatarChange($user, $dto->avatar);
        }

        // Find only the changed fields
        $changes = array_diff_assoc($newData, $currentData);

        // Only update if there are actual changes
        if (! empty($changes)) {
            $user->profile->update($changes);
        }

        return $user;
    }

    private function avatarChanged(User $user, string $newFileName): bool
    {
        return $user->getAvatarFileName() !== $newFileName;
    }

    private function handleAvatarChange(User $user, ?string $newAvatarPath): string
    {
        $extension = str($newAvatarPath)->afterLast('/')->afterLast('.')->toString();
        $newFileName = AvatarService::generateFileName($user);

        SystemDirectoryManager::instance()->avatars()->fromPath($newAvatarPath, "{$newFileName}.{$extension}");

        return "{$newFileName}.{$extension}";
    }
}
