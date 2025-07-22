<?php

declare(strict_types=1);

namespace App\Services\Page;

use App\Contracts\Page\Pageable;
use App\Contracts\Page\PageManagerInterface;
use App\DTOs\Page\CreatePageDto;
use App\DTOs\Page\PageDto;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PageService implements PageManagerInterface
{
    public function create(CreatePageDto $data, Pageable $pageable, User $creator): Page
    {
        $page = new Page([
            'name' => $data->name,
            'blocks' => $data->toEditorJSFormat(),
        ]);

        $page->pageable()->associate($pageable);
        $page->creator()->associate($creator);
        $page->save();

        $this->clearCache($pageable);

        return $page;
    }

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

        $this->clearCache($page->pageable);
        $this->clearPageCache($page);

        return $page->refresh();
    }

    public function delete(Page $page): bool
    {
        $pageable = $page->pageable;
        $result = $page->delete();

        if ($result) {
            $this->clearCache($pageable);
            $this->clearPageCache($page);
        }

        return $result;
    }

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

        return $this->mapPagesToDtos($pageable->pages()->with('creator')->orderBy('created_at', 'desc')->get(['id', 'name', 'created_at', 'updated_at', 'creator_id']), $pageable);
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

        return $pageable->pages()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function searchPages(Pageable $pageable, string $query): Collection
    {
        return $pageable->pages()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhereJsonContains('blocks', $query);
            })
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function clearCache(Pageable $pageable): void
    {
        $keys = [
            $this->generatePagesCacheKey($pageable),
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

    public function clearPageCache(Page $page): void
    {
        $cacheKey = $this->generatePageCacheKey($page);
        Cache::forget($cacheKey);

        // Also clear creator cache
        Cache::forget("pages:creator:{$page->getCreator->getKey()}");
    }

    public function generatePagesCacheKey(Pageable $pageable): string
    {
        return "pages:{$pageable->getMorphClass()}:{$pageable->getKey()}";
    }

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

    private function clearCachePattern(string $pattern): void
    {
        // Simple implementation - in production you might want to use Redis SCAN
        $prefix = str_replace('*', '', $pattern);

        // This is a simplified approach - for Redis you'd use SCAN with pattern
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget($prefix . $i);
        }
    }

    private function mapPagesToDtos(Collection $pages, Pageable $pageable): Collection
    {

        return $pages->map(fn (Page $page) => PageDto::fromArray([
            'name' => $page->name,
            'id' => $page->getKey(),
            'created_at' => $page->created_at,
            'pageable' => $pageable,
            'creator' => $page->getCreator()->toArray(),
        ]));
    }
}
