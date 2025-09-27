<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Document\Contracts\DocumentVersionContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class DocumentVersionService implements DocumentVersionContract
{
    /**
     * Create the initial document version.
     */
    public function createInitialVersion(Document $document, User $creator): DocumentVersion
    {
        $version = new DocumentVersion([
            'content' => $document->blocks,
            'created_at' => now(),
        ]);

        $version->document()->associate($document);
        $version->creator()->associate($creator);
        $version->save();

        $document->setCurrentVersion($version);

        return $version;
    }

    /**
     * Create a new version from updated document content.
     */
    public function createNewVersion(Document $document, array|string $content, User $creator): DocumentVersion
    {
        $version = new DocumentVersion([
            'content' => $content,
            'created_at' => now(),
        ]);

        $version->document()->associate($document);
        $version->creator()->associate($creator);
        $version->save();

        $document->setCurrentVersion($version);

        return $version;
    }

    /**
     * Get version history for a document.
     */
    public function getVersionHistory(Document $document): Collection
    {
        return $document->versions()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific version by ID.
     */
    public function getVersion(string $versionId): ?DocumentVersion
    {
        return DocumentVersion::with(['document', 'creator'])
            ->where('id', $versionId)
            ->first();
    }

    /**
     * Rollback document to a specific version.
     */
    public function rollbackToVersion(Document $document, DocumentVersion $version, User $user): DocumentVersion
    {
        // Create a new version with the content from the target version
        return $this->createNewVersion($document, $version->content, $user);
    }

    /**
     * Compare two versions and return differences.
     */
    public function compareVersions(DocumentVersion $oldVersion, DocumentVersion $newVersion): array
    {
        return [
            'old_version' => $oldVersion,
            'new_version' => $newVersion,
            'old_content' => $oldVersion->content,
            'new_content' => $newVersion->content,
            'created_at_diff' => $newVersion->created_at->diffForHumans($oldVersion->created_at),
        ];
    }

    /**
     * Get the latest version for a document.
     */
    public function getLatestVersion(Document $document): ?DocumentVersion
    {
        return $document->versions()
            ->with('creator')
            ->latest('created_at')
            ->first();
    }

    /**
     * Delete old versions keeping only the specified number of recent versions.
     */
    public function pruneOldVersions(Document $document, int $keepCount = 10): int
    {
        $versionsToDelete = $document->versions()
            ->orderBy('created_at', 'desc')
            ->skip($keepCount)
            ->pluck('id');

        return DocumentVersion::whereIn('id', $versionsToDelete)->delete();
    }

    /**
     * Check if a version is the current version.
     */
    public function isCurrentVersionOfItsDocument(DocumentVersion $version): bool
    {
        return $version->document->current_version_id === $version->id;
    }

    /**
     * Apply a version to its document by setting blocks and the current version.
     *
     * @throws Throwable
     */
    public function applyVersion(DocumentVersion $version): Document
    {
        $document = $version->document;

        if ($document->id !== $version->document_id) {
            throw new InvalidArgumentException('Version does not belong to the specified document');
        }

        try {
            return DB::transaction(function () use ($document, $version) {
                $document->blocks = $version->content;
                $document->setCurrentVersion($version);

                return $document;
            });
        } catch (Throwable $e) {
            Log::error('Failed to apply document version', [
                'version_id' => $version->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
