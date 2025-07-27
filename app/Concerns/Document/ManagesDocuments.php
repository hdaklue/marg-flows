<?php

declare(strict_types=1);

namespace App\Concerns\Document;

use App\DTOs\Document\DocumentDto;
use App\Facades\DocumentManager;
use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait ManagesDocuments
{
    /**
     * Get all Documents for this entity with caching.
     *
     * @return Collection<int, DocumentDto>
     */
    public function getDocuments(): Collection
    {
        return DocumentManager::getDocuments($this);
    }

    /**
     * Get recent Documents for this entity.
     *
     * @return Collection<int, Document>
     */
    public function getRecentDocument(int $limit = 10): Collection
    {
        return DocumentManager::getRecentDocuments($this, $limit);
    }

    /**
     * Search Documents by name or content.
     *
     * @return Collection<int, Document>
     */
    public function searchDocuments(string $query): Collection
    {
        return DocumentManager::searchDocuments($this, $query);
    }

    /**
     * Clear page cache for this entity.
     */
    public function clearDocumentCache(): void
    {
        DocumentManager::clearCache($this);
    }

    /**
     * Get the morphMany relationship for Documents.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
