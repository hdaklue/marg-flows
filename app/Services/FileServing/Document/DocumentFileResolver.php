<?php

declare(strict_types=1);

namespace App\Services\FileServing\Document;

use App\Models\Document;
use App\Models\User;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\FileServing\AbstractFileResolver;
use Hdaklue\Porter\Facades\Porter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Document File Resolver.
 *
 * Handles file serving for document-related files including images, videos,
 * and document attachments. Implements tenant-specific access validation
 * using Porter RBAC system and document permissions.
 */
final class DocumentFileResolver extends AbstractFileResolver
{
    public function __construct(
        private readonly DocumentDirectoryManager $directoryManager,
    ) {}

    /**
     * Create a static instance for dependency injection.
     */
    public static function make(Document $document): static
    {
        return new self(DocumentDirectoryManager::make($document));
    }

    /**
     * Check if file exists for the document.
     *
     * @param  mixed  $entity  The document entity
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return bool True if file exists, false otherwise
     */
    public function fileExists($entity, string $type, string $filename): bool
    {
        if (! $entity instanceof Document) {
            return false;
        }

        $strategy = $this->directoryManager->$type($entity->getFileStorageIdentifier());
        $disk = config('directory-document.storage.disk', 'public');

        $path = $strategy->forDocument($entity->getKey())->getDirectory() . "/{$type}/{$filename}";

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file size for document files.
     *
     * @param  mixed  $entity  The document entity
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return int|null File size in bytes, null if file doesn't exist
     */
    public function getFileSize($entity, string $type, string $filename): ?int
    {
        if (! $entity instanceof Document) {
            return null;
        }

        if (! $this->fileExists($entity, $type, $filename)) {
            return null;
        }

        $strategy = $this->directoryManager->document($entity->getFileStorageIdentifier());
        $disk = config('directory-document.storage.disk', 'public');

        $path = $strategy->forDocument($entity->getKey())->getDirectory() . "/{$type}/{$filename}";

        return Storage::disk($disk)->size($path);
    }

    /**
     * Get all files for a document by type.
     *
     * @param  Document  $document  The document entity
     * @param  string  $type  The file type (images, videos, documents)
     * @param  User|null  $user  The user requesting access
     * @return array<string> Array of filenames
     */
    public function getDocumentFiles(
        Document $document,
        string $type,
        ?User $user = null,
    ): array {
        $user ??= auth()->user();

        if (! $user || ! $this->validateAccess($document)) {
            return [];
        }

        $strategy = $this->directoryManager->document($document->getFileStorageIdentifier());
        $disk = config('directory-document.storage.disk', 'public');

        $directory = $strategy->forDocument($document->getKey())->getDirectory() . "/{$type}";

        if (! Storage::disk($disk)->exists($directory)) {
            return [];
        }

        return collect(Storage::disk($disk)->files($directory))->map(
            fn ($file) => basename($file),
        )->toArray();
    }

    /**
     * Validate if user has access to document files.
     *
     * Checks both tenant membership and document-specific permissions
     * using the Porter RBAC system.
     *
     * @param  mixed  $entity  The document entity
     * @return bool True if user has access, false otherwise
     */
    public function validateAccess($entity): bool
    {
        if (! $entity instanceof Document) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Check if user has access to the document through Porter RBAC
        $userAssignedEntities = Porter::getAssignedEntitiesByKeysByType(
            $user,
            [$entity->getKey()],
            Relation::getMorphAlias(Document::class),
        );

        return $userAssignedEntities->isNotEmpty();
    }

    /**
     * Generate secure URL for document files.
     *
     * @param  mixed  $entity  The document entity
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return string Secure URL requiring authentication
     */
    protected function generateSecureUrl($entity, string $type, string $filename): string
    {
        if (! $entity instanceof Document) {
            throw new InvalidArgumentException('Entity must be a Document instance');
        }

        return $this->directoryManager->getSecureUrl(
            $entity->getFileStorageIdentifier(),
            $type,
            $filename,
        );
    }

    /**
     * Generate temporary URL for document files.
     *
     * @param  mixed  $entity  The document entity
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @param  int|null  $expires  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    protected function generateTemporaryUrl(
        $entity,
        string $type,
        string $filename,
        ?int $expires = null,
    ): string {
        if (! $entity instanceof Document) {
            throw new InvalidArgumentException('Entity must be a Document instance');
        }

        return $this->directoryManager->getTemporaryUrl(
            $entity->getFileStorageIdentifier(),
            $type,
            $filename,
            $expires,
        );
    }

    /**
     * Perform the actual file deletion for document files.
     *
     * @param  mixed  $entity  The document entity
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $filename  The filename
     * @return bool True if deletion was successful, false otherwise
     */
    protected function performFileDelete($entity, string $type, string $filename): bool
    {
        if (! $entity instanceof Document) {
            return false;
        }

        if (! $this->fileExists($entity, $type, $filename)) {
            return false;
        }

        $strategy = $this->directoryManager->document($entity->getFileStorageIdentifier());
        $disk = config('directory-document.storage.disk', 'public');

        $path = $strategy->forDocument($entity->getKey())->getDirectory() . "/{$type}/{$filename}";

        return Storage::disk($disk)->delete($path);
    }
}
