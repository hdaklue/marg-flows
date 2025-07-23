<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Page Manager Facade
 * 
 * @method static \App\Models\Page create(\App\DTOs\Page\CreatePageDto $data, \App\Contracts\Page\Pageable $pageable, \App\Models\User $creator) Create a new page associated with a pageable entity
 * @method static \App\Models\Page update(\App\Models\Page $page, array $data) Update an existing page with new data
 * @method static bool delete(\App\Models\Page $page) Delete a page and clear associated caches
 * @method static \Illuminate\Support\Collection<int, \App\DTOs\Page\PageDto> getPages(\App\Contracts\Page\Pageable $pageable) Get all pages for a pageable entity with caching
 * @method static \Illuminate\Support\Collection<int, \App\Models\Page> getRecentPages(\App\Contracts\Page\Pageable $pageable, int $limit = 10) Get recent pages for a pageable entity
 * @method static \Illuminate\Support\Collection<int, \App\Models\Page> searchPages(\App\Contracts\Page\Pageable $pageable, string $query) Search pages by name or content
 * @method static void clearCache(\App\Contracts\Page\Pageable $pageable) Clear all cached data for a pageable entity
 * @method static void clearPageCache(\App\Models\Page $page) Clear cache for a specific page
 * @method static string generatePagesCacheKey(\App\Contracts\Page\Pageable $pageable) Generate cache key for pageable entity's pages
 * @method static string generatePageCacheKey(\App\Models\Page $page) Generate cache key for a specific page
 * 
 * @see \App\Services\Page\PageService
 */
final class PageManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'page.manager';
    }
}