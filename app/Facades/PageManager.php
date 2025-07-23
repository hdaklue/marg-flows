<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Page Manager Facade
 * 
 * @method static Page create(CreatePageDto $data, Pageable $pageable, User $creator) Create a new page associated with a pageable entity
 * @method static Page update(Page $page, array $data) Update an existing page with new data
 * @method static bool delete(Page $page) Delete a page and clear associated caches
 * @method static Collection<int, \App\DTOs\Page\PageDto> getPages(Pageable $pageable) Get all pages for a pageable entity with caching
 * @method static Collection<int, Page> getRecentPages(Pageable $pageable, int $limit = 10) Get recent pages for a pageable entity
 * @method static Collection<int, Page> searchPages(Pageable $pageable, string $query) Search pages by name or content
 * @method static void clearCache(Pageable $pageable) Clear all cached data for a pageable entity
 * @method static void clearPageCache(Page $page) Clear cache for a specific page
 * @method static string generatePagesCacheKey(Pageable $pageable) Generate cache key for pageable entity's pages
 * @method static string generatePageCacheKey(Page $page) Generate cache key for a specific page
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