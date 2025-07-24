<?php

declare(strict_types=1);

namespace App\Services\Page;

use App\Contracts\Page\Pageable;
use App\Contracts\Page\PageManagerInterface;
use App\Contracts\Role\AssignableEntity;
use App\DTOs\Page\CreatePageDto;
use App\DTOs\Page\PageDto;
use App\Facades\RoleManager;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PageService implements PageManagerInterface
{
    /**
     * Create a new page associated with a pageable entity.
     *
     * @param  CreatePageDto  $data  The page creation data
     * @param  Pageable  $pageable  The entity this page belongs to
     * @param  User  $creator  The user creating the page
     * @return Page The created page instance
     */
    public function create(CreatePageDto $data, Pageable $pageable, User $creator): Page
    {
        $page = new Page([
            'name' => $data->name,
            'blocks' => $data->toEditorJSFormat(),
        ]);

        assert($pageable instanceof Model);
        $page->pageable()->associate($pageable);
        $page->creator()->associate($creator);
        $page->save();

        $this->clearCache($pageable);

        return $page;
    }

    /**
     * Update an existing page with new data.
     *
     * @param  Page  $page  The page to update
     * @param  array{name?: string, blocks?: array<mixed>}  $data  The update data
     * @return Page The updated page instance
     */
    public function update(Page $page, array $data): Page
    {
        if (isset($data['name'])) {
            $page->name = $data['name'];
        }

        if (isset($data['blocks'])) {
            $page->blocks = [
                'time' => now()->timestamp,
                'blocks' => $data['blocks'],
                'version' => '2.28.2',
            ];
        }

        $page->save();

        assert($page->pageable instanceof Pageable);
        $this->clearCache($page->pageable);
        $this->clearPageCache($page);

        return $page->refresh();
    }

    /**
     * Delete a page and clear associated caches.
     *
     * @param  Page  $page  The page to delete
     * @return bool True if deletion was successful
     */
    public function delete(Page $page): bool
    {
        $pageable = $page->pageable;
        assert($pageable instanceof Pageable);
        $result = $page->delete();

        if ($result) {
            $this->clearCache($pageable);
            $this->clearPageCache($page);
        }

        return $result;
    }

    /**
     * Returns pages of a pageable entity can be accessed by User.
     */
    public function getPagesForUser(Pageable $pageable, AssignableEntity $user): Collection
    {
        $pageablePages = $this->getPagesForPageable($pageable);

        $pageKeys = $pageablePages->pluck('id')->toArray();

        return RoleManager::getAssignedEntitiesByKeysByType(
            $user,
            $pageKeys,
            Relation::getMorphAlias(Page::class),
        )->sortByDesc('updated_at');
    }

    /**
     * Get all pages for a pageable entity with caching.
     *
     * @param  Pageable  $pageable  The entity to get pages for
     * @return Collection<int, PageDto> Collection of page DTOs
     */
    public function getPages(Pageable $pageable): Collection
    {

        if (config('page.should_cache', true)) {
            $cachedPages = Cache::remember(
                $this->generatePagesCacheKey($pageable),
                now()->addMinutes(config('page.cache_ttl.list', 60)),
                fn () => $pageable->pages()->with('creator')->orderBy('created_at', 'desc')->get(['id', 'name', 'created_at', 'updated_at', 'creator_id']),
            );

            return $this->mapPagesToDtos($cachedPages, $pageable);
        }

        /** @var Collection<int, Page> $pages */
        $pages = $pageable->pages()->with('creator')->orderBy('created_at', 'desc')->get(['id', 'name', 'created_at', 'updated_at', 'creator_id']);

        return $this->mapPagesToDtos($pages, $pageable);
    }

    public function getPage(string $pageId): ?Page
    {
        $page = Page::with(['creator', 'pageable'])->find($pageId);

        if (! $page) {
            return null;
        }

        if (config('page.should_cache', true)) {
            $cacheKey = $this->generatePageCacheKey($page);

            return Cache::remember($cacheKey, now()->addMinutes(config('page.cache_ttl.page', 1440)), fn () => $page);
        }

        return $page;
    }

    public function getPagesByCreator(User $creator): Collection
    {
        if (config('page.should_cache', true)) {
            return Cache::remember(
                "pages:creator:{$creator->getKey()}",
                now()->addMinutes(config('page.cache_ttl.creator', 60)),
                fn () => Page::where('creator_id', $creator->getKey())
                    ->with(['pageable', 'creator'])
                    ->orderBy('created_at', 'desc')
                    ->get(),
            );
        }

        return Page::where('creator_id', $creator->getKey())
            ->with(['pageable', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent pages for a pageable entity.
     *
     * @param  Pageable  $pageable  The entity to get pages for
     * @param  int  $limit  Maximum number of pages to return
     * @return Collection<int, Page> Collection of recent pages
     */
    public function getRecentPages(Pageable $pageable, int $limit = 10): Collection
    {
        if (config('page.should_cache', true)) {
            return Cache::remember(
                "pages:recent:{$pageable->getMorphClass()}:{$pageable->getKey()}:{$limit}",
                now()->addMinutes(config('page.cache_ttl.recent', 60)),
                fn () => $pageable->pages()
                    ->with('creator')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get(),
            );
        }

        /** @var Collection<int, Page> $pages */
        $pages = $pageable->pages()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $pages;
    }

    /**
     * Search pages by name or content.
     *
     * @param  Pageable  $pageable  The entity to search pages within
     * @param  string  $query  The search query
     * @return Collection<int, Page> Collection of matching pages
     */
    public function searchPages(Pageable $pageable, string $query): Collection
    {
        /** @var Collection<int, Page> $pages */
        $pages = $pageable->pages()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhereJsonContains('blocks', $query);
            })
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return $pages;
    }

    /**
     * Clear all cached data for a pageable entity.
     *
     * @param  Pageable  $pageable  The entity to clear cache for
     */
    public function clearCache(Pageable $pageable): void
    {
        $keys = [
            $this->generatePagesCacheKey($pageable),
            "pages:pageable:{$pageable->getMorphClass()}:{$pageable->getKey()}:*",
            "pages:recent:{$pageable->getMorphClass()}:{$pageable->getKey()}:*",
        ];

        foreach ($keys as $key) {
            if (str_contains($key, '*')) {
                $this->clearCachePattern($key);
            } else {
                Cache::forget($key);
            }
        }
    }

    /**
     * Clear cache for a specific page.
     *
     * @param  Page  $page  The page to clear cache for
     */
    public function clearPageCache(Page $page): void
    {
        $cacheKey = $this->generatePageCacheKey($page);
        Cache::forget($cacheKey);

        // Also clear creator cache
        Cache::forget("pages:creator:{$page->getCreator()->getKey()}");
    }

    /**
     * Generate cache key for pageable entity's pages.
     *
     * @param  Pageable  $pageable  The entity to generate key for
     * @return string The cache key
     */
    public function generatePagesCacheKey(Pageable $pageable): string
    {
        return "pages:{$pageable->getMorphClass()}:{$pageable->getKey()}";
    }

    /**
     * Generate cache key for a specific page.
     *
     * @param  Page  $page  The page to generate key for
     * @return string The cache key
     */
    public function generatePageCacheKey(Page $page): string
    {
        return "page:{$page->getKey()}:" . $this->generateContentHash($page);
    }

    public function generateContentHash(Page $page): string
    {
        $content = serialize([
            'name' => $page->name,
            'blocks' => $page->blocks,
        ]);

        return md5($content);
    }

    public function bulkClearCache(Collection $pageables): void
    {
        $pageables->each(function (Pageable $pageable) {
            $this->clearCache($pageable);
        });
    }

    /**
     * Get all pages for a pageable entity with caching.
     * Cache key automatically invalidates when pages are added/removed.
     */
    public function getPagesForPageable(Pageable $pageable): Collection
    {
        $pages = Page::whereHasMorph('pageable',
            $pageable->getMorphClass(), function ($query) use ($pageable) {
                $query
                    ->where('id', $pageable->getKey());
            })->get();

        if (config('page.should_cache')) {
            $cacheKey = "pages:pageable:{$pageable->getMorphClass()}:{$pageable->getKey()}:" . md5(serialize($pages->pluck('id')->toArray()));

            return Cache::remember($cacheKey, now()->addDay(), fn () => $pages);
        }

        return $pages;
    }

    /**
     * Clear cache entries matching a pattern.
     *
     * @param  string  $pattern  The cache key pattern to clear
     */
    private function clearCachePattern(string $pattern): void
    {
        // Simple implementation - in production you might want to use Redis SCAN
        $prefix = str_replace('*', '', $pattern);

        // This is a simplified approach - for Redis you'd use SCAN with pattern
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget($prefix . $i);
        }
    }

    /**
     * Map pages to DTOs for API responses.
     *
     * @param  Collection<int, Page>  $pages  The pages to map
     * @param  Pageable  $pageable  The pageable entity
     * @return Collection<int, PageDto> Collection of page DTOs
     */
    private function mapPagesToDtos(Collection $pages, Pageable $pageable): Collection
    {

        return $pages->map(fn (Page $page) => PageDto::fromArray([
            'name' => $page->getAttribute('name'),
            'id' => $page->getKey(),
            'created_at' => $page->getAttribute('created_at'),
            'updated_at' => $page->getAttribute('updated_at'),
            'pageable' => $pageable,
            'creator' => $page->getCreator()->toArray(),
        ]));
    }
}
