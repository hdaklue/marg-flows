<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Contracts\Document\Documentable;
use App\Contracts\Document\DocumentManagerInterface;
use App\DTOs\Document\CreateDocumentDto;
use App\DTOs\Document\DocumentDto;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use Hdaklue\MargRbac\Facades\RoleManager;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class DocumentService implements DocumentManagerInterface
{
    /**
     * Create a new page associated with a documentable entity.
     *
     * @param  CreateDocumentDto  $data  The page creation data
     * @param  Documentable  $documentable  The entity this page belongs to
     * @param  User  $creator  The user creating the page
     * @return Document The created page instance
     */
    public function create(CreateDocumentDto $data, Documentable $documentable, User $creator): Document
    {
        $document = new Document([
            'name' => $data->name,
            'blocks' => $data->toEditorJSFormat(),
        ]);

        assert($documentable instanceof Model);
        $document->documentable()->associate($documentable);
        $document->creator()->associate($creator);
        $document->save();
        $document->addParticipant($creator, RoleEnum::ADMIN);

        $this->clearCache($documentable);

        return $document;
    }

    /**
     * Update an existing page with new data.
     *
     * @param  Document  $document  The page to update
     * @param  array{name?: string, blocks?: array<mixed>}  $data  The update data
     * @return Document The updated page instance
     */
    public function update(Document $document, array $data): Document
    {

        if (isset($data['name'])) {
            $document->name = $data['name'];
        }

        if (isset($data['blocks'])) {
            $document->blocks = $data['blocks'];
        }
        $document->save();

        assert($document->documentable instanceof Documentable);
        $this->clearCache($document->documentable);
        $this->clearDocumentsCache($document);

        return $document->refresh();
    }

    public function updateBlocks(Document $document, array|string $blocks)
    {

        $document->updateBlocks($blocks);
        $this->clearCache($document->documentable);
        $this->clearDocumentsCache($document);
    }

    /**
     * Delete a page and clear associated caches.
     *
     * @param  Document  $document  The page to delete
     * @return bool True if deletion was successful
     */
    public function delete(Document $document): bool
    {
        $documentable = $document->documentable;
        assert($documentable instanceof Documentable);
        $result = $document->delete();

        if ($result) {
            $this->clearCache($documentable);
            $this->clearDocumentsCache($document);
        }

        return $result;
    }

    /**
     * Returns pages of a documentable entity can be accessed by User.
     */
    public function getDocumentsForUser(Documentable $documentable, User $user): Collection
    {
        $documentablePages = $this->getDocumentsFordocumentable($documentable);

        $pageKeys = $documentablePages->pluck('id')->toArray();

        return RoleManager::getAssignedEntitiesByKeysByType(
            $user,
            $pageKeys,
            Relation::getMorphAlias(Document::class),
        )->sortByDesc('updated_at');
    }

    /**
     * Get all pages for a documentable entity with caching.
     *
     * @param  Documentable  $documentable  The entity to get pages for
     * @return Collection<int, DocumentDto> Collection of page DTOs
     */
    public function getDocuments(Documentable $documentable): Collection
    {

        if (config('document.should_cache', true)) {
            $cahcedDocuments = Cache::remember(
                $this->generateDocumentsCacheKey($documentable),
                now()->addMinutes(config('document.cache_ttl.list', 60)),
                fn () => $documentable->documents()->with('creator')->orderBy('created_at', 'desc')->get(['id', 'name', 'created_at', 'updated_at', 'creator_id']),
            );

            return $this->mapDocumentsToDtos($cahcedDocuments, $documentable);
        }

        /** @var Collection<int, Document> $documents */
        $documents = $documentable->documents()->with('creator')->orderBy('created_at', 'desc')->get(['id', 'name', 'created_at', 'updated_at', 'creator_id']);

        return $this->mapDocumentsToDtos($documents, $documentable);
    }

    public function getDocument(string $documentId): ?Document
    {
        $page = Document::where('id', $documentId)
            ->with(['creator', 'documentable'])->firstOrFail();

        if (config('document.should_cache', true)) {
            $cacheKey = $this->generateDocumentCacheKey($page);

            return Cache::remember($cacheKey, now()->addMinutes(config('document.cache_ttl.document', 1440)), fn () => $page);
        }

        return $page;
    }

    public function getDocumentDto(string $documentId): ?DocumentDto
    {
        return DocumentDto::fromModel($this->getDocument($documentId));
    }

    public function getDocumentsByCreator(User $creator): Collection
    {
        if (config('document.should_cache', true)) {
            return Cache::remember(
                "documents:creator:{$creator->getKey()}",
                now()->addMinutes(config('document.cache_ttl.creator', 60)),
                fn () => Document::where('creator_id', $creator->getKey())
                    ->with(['documentable', 'creator'])
                    ->orderBy('created_at', 'desc')
                    ->get(),
            );
        }

        return Document::where('creator_id', $creator->getKey())
            ->with(['documentable', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent pages for a documentable entity.
     *
     * @param  Documentable  $documentable  The entity to get pages for
     * @param  int  $limit  Maximum number of pages to return
     * @return Collection<int, Document> Collection of recent pages
     */
    public function getRecentDocuments(Documentable $documentable, int $limit = 10): Collection
    {
        if (config('document.should_cache', true)) {
            return Cache::remember(
                "documents:recent:{$documentable->getMorphClass()}:{$documentable->getKey()}:{$limit}",
                now()->addMinutes(config('document.cache_ttl.recent', 60)),
                fn () => $documentable->documents()
                    ->with('creator')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get(),
            );
        }

        /** @var Collection<int, Document> $pages */
        $pages = $documentable->documents()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $pages;
    }

    /**
     * Search pages by name or content.
     *
     * @param  Documentable  $documentable  The entity to search pages within
     * @param  string  $query  The search query
     * @return Collection<int, Document> Collection of matching pages
     */
    public function searchDocuments(Documentable $documentable, string $query): Collection
    {
        /** @var Collection<int, Document> $pages */
        $pages = $documentable->documents()
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
     * Clear all cached data for a documentable entity.
     *
     * @param  Documentable  $documentable  The entity to clear cache for
     */
    public function clearCache(Documentable $documentable): void
    {
        $keys = [
            $this->generateDocumentsCacheKey($documentable),
            "documents:documentable:{$documentable->getMorphClass()}:{$documentable->getKey()}:*",
            "documents:recent:{$documentable->getMorphClass()}:{$documentable->getKey()}:*",
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
     * @param  Document  $document  The page to clear cache for
     */
    public function clearDocumentsCache(Document $document): void
    {
        $cacheKey = $this->generateDocumentCacheKey($document);
        Cache::forget($cacheKey);

        // Also clear creator cache
        Cache::forget("documents:creator:{$document->getCreator()->getKey()}");
    }

    /**
     * Generate cache key for documentable entity's pages.
     *
     * @param  Documentable  $documentable  The entity to generate key for
     * @return string The cache key
     */
    public function generateDocumentsCacheKey(Documentable $documentable): string
    {
        return "documents:{$documentable->getMorphClass()}:{$documentable->getKey()}";
    }

    /**
     * Generate cache key for a specific page.
     *
     * @param  Document  $page  The page to generate key for
     * @return string The cache key
     */
    public function generateDocumentCacheKey(Document $page): string
    {
        return "document:{$page->getKey()}:" . $this->generateContentHash($page);
    }

    public function generateContentHash(Document $page): string
    {
        $content = json_encode([
            'name' => $page->name,
            'blocks' => $page->blocks,
        ]);

        return md5($content);
    }

    public function bulkClearCache(Collection $documentables): void
    {
        $documentables->each(function (Documentable $documentable) {
            $this->clearCache($documentable);
        });
    }

    /**
     * Get all pages for a documentable entity with caching.
     * Cache key automatically invalidates when pages are added/removed.
     */
    public function getDocumentsFordocumentable(Documentable $documentable): Collection
    {
        $pages = Document::whereHasMorph('documentable',
            $documentable->getMorphClass(), function ($query) use ($documentable) {
                $query
                    ->where('id', $documentable->getKey());
            })->get();

        if (config('document.should_cache')) {
            $cacheKey = "documents:documentable:{$documentable->getMorphClass()}:{$documentable->getKey()}:" . md5(serialize($pages->pluck('id')->toArray()));

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
     * @param  Collection<int, Document>  $documents  The documents to map
     * @param  Documentable  $documentable  The documentable entity
     * @return Collection<int, DocumentDto> Collection of page DTOs
     */
    private function mapDocumentsToDtos(Collection $documents, Documentable $documentable): Collection
    {

        return $documents->map(fn (Document $document) => DocumentDto::fromArray([
            'name' => $document->getAttribute('name'),
            'id' => $document->getKey(),
            'created_at' => $document->getAttribute('created_at'),
            'updated_at' => $document->getAttribute('updated_at'),
            'documentable' => $documentable,
            'creator' => $document->getCreator()->toArray(),
        ]));
    }
}
