<?php

declare(strict_types=1);

namespace App\Services\Recency;

use App\Models\Recent;
use App\Models\User;
use App\Services\Recency\Contracts\Recentable;
use Exception;

final class RecencyService
{
    public static function tap(User $user, Recentable $recentable)
    {
        try {
            // Skip if recently updated - check before any DB operations
            $recent = Recent::query()
                ->where('user_id', $user->id)
                ->where('recentable_type', $recentable->getRecentType())
                ->where('recentable_id', $recentable->getRecentKey())
                ->where('tenant_id', $user->activeTenant()->getKey())
                ->where('interacted_at', '>', now()->subMinutes(config('recency.throttle_minutes')))
                ->exists();

            if ($recent) {
                return; // Skip update if within throttle period
            }

            Recent::query()->updateOrInsert([
                'user_id' => $user->id,
                'recentable_type' => $recentable->getRecentType(),
                'recentable_id' => $recentable->getRecentKey(),
                'tenant_id' => $user->activeTenant()->getKey(),
            ], [
                'interacted_at' => now(),
            ]);
        } catch (Exception $e) {
            // Log the error but don't throw it to prevent breaking the main flow
            logger()->warning('Failed to tap recent interaction', [
                'user_id' => $user->id,
                'recentable_type' => $recentable->getRecentType(),
                'recentable_id' => $recentable->getRecentKey(),
                'error' => $e->getMessage(),
            ]);
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

    public static function forUser(User $user, ?int $limit = null): RecentableCollection
    {
        return Recent::query()
            ->forUser($user)
            ->byTenant($user->activeTenant())
            ->latest('interacted_at')
            ->limit($limit ?? config('recency.default_limit'))
            ->get();
    }
}
