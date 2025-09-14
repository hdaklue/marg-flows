<?php

declare(strict_types=1);

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
