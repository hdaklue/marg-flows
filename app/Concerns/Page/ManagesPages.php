<?php

declare(strict_types=1);

namespace App\Concerns\Page;

use App\Facades\PageManager;
use App\Models\Page;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait ManagesPages
{
    /**
     * Get all pages for this entity with caching.
     *
     * @return \Illuminate\Support\Collection<int, \App\DTOs\Page\PageDto>
     */
    public function getPages(): Collection
    {
        return PageManager::getPages($this);
    }

    /**
     * Get recent pages for this entity.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Page>
     */
    public function getRecentPages(int $limit = 10): Collection
    {
        return PageManager::getRecentPages($this, $limit);
    }

    /**
     * Search pages by name or content.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Page>
     */
    public function searchPages(string $query): Collection
    {
        return PageManager::searchPages($this, $query);
    }

    /**
     * Clear page cache for this entity.
     */
    public function clearPageCache(): void
    {
        PageManager::clearCache($this);
    }

    /**
     * Get the morphMany relationship for pages.
     */
    public function pages(): MorphMany
    {
        return $this->morphMany(Page::class, 'pageable');
    }
}
