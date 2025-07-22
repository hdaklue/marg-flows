<?php

declare(strict_types=1);

namespace App\Contracts\Page;

use App\DTOs\Page\CreatePageDto;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Collection;

interface PageManagerInterface
{
    /**
     * Create a new page using PageCreateData DTO.
     */
    public function create(CreatePageDto $data, Pageable $pageable, User $creator): Page;

    /**
     * Update an existing page.
     */
    public function update(Page $page, array $data): Page;

    /**
     * Delete a page.
     */
    public function delete(Page $page): bool;

    /**
     * Get all pages for a pageable entity.
     *
     * @return Collection<Page>
     */
    public function getPages(Pageable $pageable): Collection;

    /**
     * Get a specific page by ID with content-based caching.
     */
    public function getPage(string $pageId): ?Page;

    /**
     * Get pages created by a specific user.
     *
     * @return Collection<Page>
     */
    public function getPagesByCreator(User $creator): Collection;

    /**
     * Get recent pages for a pageable entity.
     *
     * @return Collection<Page>
     */
    public function getRecentPages(Pageable $pageable, int $limit = 10): Collection;

    /**
     * Search pages by name or content.
     *
     * @return Collection<Page>
     */
    public function searchPages(Pageable $pageable, string $query): Collection;

    /**
     * Clear cache for a specific pageable entity.
     */
    public function clearCache(Pageable $pageable): void;

    /**
     * Clear cache for a specific page.
     */
    public function clearPageCache(Page $page): void;

    /**
     * Generate cache key for pageable pages.
     */
    public function generatePagesCacheKey(Pageable $pageable): string;

    /**
     * Generate content-based cache key for a specific page.
     */
    public function generatePageCacheKey(Page $page): string;

    /**
     * Generate content hash from page name and blocks.
     */
    public function generateContentHash(Page $page): string;

    /**
     * Bulk clear cache for multiple pageable entities.
     *
     * @param  Collection<Pageable>  $pageables
     */
    public function bulkClearCache(Collection $pageables): void;
}
