<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\Facade;

/**
 * Document Manager Facade.
 *
 * @method static \App\Models\Document create(\App\DTOs\Document\CreateDocumentDto $data, \App\Contracts\Document\Documentable $documentable, \App\Models\User $creator) Create a new document associated with a documentable entity
 * @method static \App\Models\Document update(\App\Models\Document $document, array $data) Update an existing document with new data
 * @method static bool delete(\App\Models\Document $document) Delete a document and clear associated caches
 * @method static \Illuminate\Support\Collection<int, \App\DTOs\Document\DocumentDto> getDocuments(\App\Contracts\Document\Documentable $documentable) Get all documents for a documentable entity with caching
 * @method static \Illuminate\Support\Collection<int, \App\Models\Document> getRecentDocuments(\App\Contracts\Document\Documentable $documentable, int $limit = 10) Get recent documents for a documentable entity
 * @method static \Illuminate\Support\Collection<int, \App\Models\Document> searchDocuments(\App\Contracts\Document\Documentable $documentable, string $query) Search documents by name or content
 * @method static void clearCache(\App\Contracts\Document\Documentable $documentable) Clear all cached data for a documentable entity
 * @method static void clearDocumentCache(\App\Models\Document $document) Clear cache for a specific document
 * @method static string generateDocumentsCacheKey(\App\Contracts\Document\Documentable $documentable) Generate cache key for documentable entity's documents
 * @method static string generateDocumentCacheKey(\App\Models\Document $document) Generate cache key for a specific document
 *
 * @see DocumentService
 */
final class DocumentManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'document.manager';
    }
}
