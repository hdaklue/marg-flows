<?php

namespace App\Services\Recency;

use App\Services\Recency\Data\RecentableData;
use Illuminate\Database\Eloquent\Collection;

class RecentableCollection extends Collection
{
    public function asDataCollection(): \Illuminate\Support\Collection
    {
        return $this->map(function ($item) {
            return RecentableData::fromRecentable($item->recentable, $item->getTenant());
        });
    }

    public function asFlatDataCollection(): \Illuminate\Support\Collection
    {
        return $this->map(function ($item) {
            return RecentableData::fromRecentable($item->recentable, $item->getTenant())->toArray();
        });
    }
}
