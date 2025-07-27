<?php

declare(strict_types=1);

namespace App\Contracts\Document;

use App\DTOs\Document\CreateDocumentDto;
use App\DTOs\Document\DocumentDto;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;

interface DocumentManagerInterface
{
    /**
     * Create a new document using documentCreateData DTO.
     */
    public function create(CreateDocumentDto $data, Documentable $documentable, User $creator): Document;

    /**
     * Update an existing document.
     */
    public function update(Document $document, array $data): Document;

    /**
     * Delete a document.
     */
    public function delete(Document $document): bool;

    /**
     * Get all documents for a documentable entity.
     *
     * @return Collection<int, DocumentDto>
     */
    public function getDocuments(Documentable $documentable): Collection;

    /**
     * Get a specific document by ID with content-based caching.
     */
    public function getDocument(string $documentId): ?Document;

    /**
     * Get documents created by a specific user.
     *
     * @return Collection<Document>
     */
    public function getDocumentsByCreator(User $creator): Collection;

    /**
     * Get recent documents for a documentable entity.
     *
     * @return Collection<Document>
     */
    public function getRecentDocuments(Documentable $documentable, int $limit = 10): Collection;

    /**
     * Search documents by name or content.
     *
     * @return Collection<Document>
     */
    public function searchDocuments(Documentable $documentable, string $query): Collection;

    /**
     * Clear cache for a specific documentable entity.
     */
    public function clearCache(Documentable $documentable): void;

    /**
     * Clear cache for a specific document.
     */
    public function clearDocumentsCache(Document $document): void;

    /**
     * Generate cache key for documentable documents.
     */
    public function generateDocumentsCacheKey(Documentable $documentable): string;

    /**
     * Generate content-based cache key for a specific document.
     */
    public function generateDocumentCacheKey(Document $document): string;

    /**
     * Generate content hash from document name and blocks.
     */
    public function generateContentHash(Document $document): string;

    /**
     * Bulk clear cache for multiple documentable entities.
     *
     * @param  Collection<Documentable>  $documentables
     */
    public function bulkClearCache(Collection $documentables): void;
}
