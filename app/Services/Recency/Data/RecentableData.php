<?php

declare(strict_types=1);

namespace App\Services\Recency\Data;

use App\Models\Tenant;
use App\Services\Recency\Contracts\Recentable;
use App\Services\Recency\UrlResolver;
use WendellAdriel\ValidatedDTO\SimpleDTO;

final class RecentableData extends SimpleDTO
{
    public string $title;

    public string|int $key;

    public string $type;

    public string $url;

    public ?string $color;

    public static function fromRecentable(Recentable $recentable, Tenant $tenant): static
    {
        return self::fromArray([
            'title' => $recentable->getRecentLabel(),
            'key' => $recentable->getRecentKey(),
            'type' => $recentable->getRecentType(),
            'url' => self::resolveUrl($recentable, $tenant),
            'color' => self::color($recentable->getRecentType()),
        ]);
    }

    public static function color(string $type): string
    {
        return match ($type) {
            default => 'zinc',
        };
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
