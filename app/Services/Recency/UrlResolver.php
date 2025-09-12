<?php

namespace App\Services\Recency;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Tenant;
use App\Services\Recency\Contracts\Recentable;

class UrlResolver
{
    public static function resolve(Recentable $recentable, Tenant $tenant)
    {
        return match ($recentable->getRecentType()) {
            'document' => static::document($recentable, $tenant),
            'flow' => static::flow($recentable, $tenant),
            default => throw new \InvalidArgumentException('Unknown type: '
            . $recentable->getRecentType()),
        };
    }

    private static function document(Recentable $recentable, Tenant $tenant): string
    {
        return DocumentResource::getUrl('view', [
            'record' => $recentable->getRecentKey(),
            'tenant' => $tenant->getKey(),
        ]);
    }

    private static function flow(Recentable $recentable, Tenant $tenant): string
    {
        return FlowResource::getUrl('view', [
            'record' => $recentable->getRecentKey(),
            'tenant' => $tenant->getKey(),
        ]);
    }
}
