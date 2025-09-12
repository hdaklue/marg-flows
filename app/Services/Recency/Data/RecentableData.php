<?php

namespace App\Services\Recency\Data;

use App\Models\Tenant;
use App\Services\Recency\Contracts\Recentable;
use App\Services\Recency\UrlResolver;
use WendellAdriel\ValidatedDTO\SimpleDTO;

class RecentableData extends SimpleDTO
{
    public string $title;
    public string|int $key;

    public string $type;
    public string $url;

    public static function fromRecentable(Recentable $recentable, Tenant $tenant): static
    {
        return static::fromArray([
            'title' => $recentable->getRecentLabel(),
            'key' => $recentable->getRecentKey(),
            'type' => $recentable->getRecentType(),
            'url' => static::resolveUrl($recentable, $tenant),
        ]);
    }

    protected static function resolveUrl(Recentable $recentable, Tenant $tenant)
    {
        return UrlResolver::resolve($recentable, $tenant);
    }

    protected function casts(): array
    {
        return [];
    }

    protected function defaults(): array
    {
        return [];
    }
}
