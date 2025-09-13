<?php

declare(strict_types=1);

namespace App\Services\Recency;

use App\Models\Recent;
use App\Services\Recency\Data\RecentableData;
use Illuminate\Database\Eloquent\Collection;

final class RecentableCollection extends Collection
{
    public function asDataCollection(): \Illuminate\Support\Collection
    {
        return $this->map(function ($item) {
            return RecentableData::fromRecentable($item->recentable, $item->getTenant());
        })->filter();
    }

    public function asFlatDataCollection(): \Illuminate\Support\Collection
    {
        return $this->map(function (Recent $item) {
            return RecentableData::fromRecentable($item->recentable, $item->getTenant())->toArray();
        })->filter();
    }

    public function whereTypeIs(string $type)
    {
        return $this->filter(fn($item) => $item->recentable?->getRecentType() === $type);
    }
}
