<?php

namespace App\Services\Recency\Concerns;

trait RecentableModel
{
    public function getRecentKey(): string|int
    {
        return $this->getKey();
    }

    public function getRecentType(): string
    {
        return $this->getMorphClass();
    }
}
