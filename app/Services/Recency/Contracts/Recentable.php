<?php

declare(strict_types=1);

namespace App\Services\Recency\Contracts;

interface Recentable
{
    /**
     * Unique identifier for this recentable object.
     */
    public function getRecentKey(): string|int;

    /**
     * Type identifier for polymorphic storage.
     * Could be an Eloquent morph class, an external API resource, or something else.
     */
    public function getRecentType(): string;

    /**
     * Optional: label or title for displaying in a dashboard widget.
     */
    public function getRecentLabel(): ?string;
}
