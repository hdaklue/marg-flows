<?php

namespace App\Services\Recency;

use App\Models\Recent;
use App\Models\User;
use App\Services\Recency\Contracts\Recentable;
use Exception;
use Illuminate\Database\Eloquent\Collection;

//TODO:should skip if the last update is less than an hour
class RecencyService
{
    public static function tap(User $user, Recentable $recentable)
    {
        try {
            Recent::query()->updateOrInsert([
                'user_id' => $user->id,
                'recentable_type' => $recentable->getRecentType(),
                'recentable_id' => $recentable->getRecentKey(),
                'tenant_id' => $user->activeTenant()->getKey(),
            ], [
                'interacted_at' => now(),
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function forUserOn(
        User $user,
        string $recentableType,
        int $limit = 10,
    ): RecentableCollection {
        return Recent::query()
            ->byTenant($user->activeTenant())
            ->forUser($user)
            ->where('recentable_type', $recentableType)
            ->latest('interacted_at')
            ->limit($limit)
            ->get(); // returns actual models
    }

    public static function forUser(User $user, int $limit = 10): RecentableCollection
    {
        return Recent::query()
            ->forUser($user)
            ->byTenant($user->activeTenant())
            ->latest('interacted_at')
            ->limit($limit)
            ->get();
    }
}
