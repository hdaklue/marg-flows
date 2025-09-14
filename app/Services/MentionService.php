<?php

declare(strict_types=1);

namespace App\Services;

use Hdaklue\MargRbac\Contracts\Role\RoleableEntity;
use Illuminate\Support\Collection;

final class MentionService
{
    public function getMentionables(RoleableEntity $entity): Collection
    {
        if (config('mention.cache.enabled')) {
            $cacheKey = $this->generateCacheKey(
                $entity,
                $entity->getParticipants(),
            );

            return cache()->remember(
                $cacheKey,
                config('mention.cache.ttl'),
                function () use ($entity) {
                    return $entity
                        ->getParticipants()
                        ->pluck('model')
                        ->map(function ($participant) {
                            return [
                                'id' => $participant->getModel()->getKey(),
                                'name' => $participant->getModel()->name,
                                'avatar' => $participant->getModel()->avatar,
                            ];
                        });
                },
            );
        }

        return $entity
            ->getParticipants()
            ->pluck('model')
            ->map(fn ($participant) => [
                'id' => $participant->getModel()->getKey(),
                'name' => $participant->getModel()->name,
                'avatar' => $participant->getModel()->avatar,
            ]);
    }

    private function generateCacheKey(
        RoleableEntity $entity,
        Collection $participants,
    ): string {
        $ids = $participants->pluck('model')->pluck('id')->toArray();

        return
            'mentions_'
            . md5(serialize($ids))
            . '_'
            . $entity->getMorphClass()
            . '_'
            . $entity->getKey();
    }
}
