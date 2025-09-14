<?php

declare(strict_types=1);

namespace App\Services\Directory\Managers;

use App\Models\Document;
use App\Services\Directory\AbstractDirectoryManager;
use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use App\Services\Directory\Contracts\DocumentDirectoryManagerContract;
use App\Services\Directory\Strategies\ChunksStorageStrategy;
use App\Services\Directory\Strategies\ImageStorageStrategy;
use App\Services\Directory\Strategies\VideoStorageStrategy;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\Facades\LaraPath;
use InvalidArgumentException;

/**
 * Document Directory Manager.
 *
 * Self-contained document storage manager that uses directory-document configuration.
 * Handles tenant-specific document storage operations including documents, chunks,
 * videos, and associated utility methods. Provides centralized management for all
 * document-related file operations within tenant isolation.
 */
final class DocumentDirectoryManager extends AbstractDirectoryManager implements DocumentDirectoryManagerContract
{
    private Document $document;

    private function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Create a static instance for document operations.
     *
     * @param  Document  $document  The document instance
     * @return static Configured instance for the document
     */
    public static function make(Document $document): static
    {
        return new self($document);
    }

    /**
     * Get images storage strategy for the document.
     *
     * @return ImageStorageStrategy Configured image storage strategy
     */
    public function images(): ImageStorageStrategy
    {
        $baseDirectory = $this->getDocumentBaseDirectory('images');

        return new ImageStorageStrategy($baseDirectory);
    }

    /**
     * Get videos storage strategy for the document.
     *
     * @return VideoStorageStrategy Configured video storage strategy
     */
    public function videos(): VideoStorageStrategy
    {
        $baseDirectory = $this->getDocumentBaseDirectory('videos');

        return new VideoStorageStrategy($baseDirectory);
    }

    /**
     * Get chunks storage strategy for file upload sessions.
     *
     * @return ChunksStorageStrategyContract Configured chunks storage strategy
     */
    public function chunks(): ChunksStorageStrategyContract
    {
        return new ChunksStorageStrategy($this->document->getTenant()->getKey());
    }

    /**
     * Get video storage strategy for the document with custom subdirectory.
     *
     * @param  string  $baseDirectory  The base directory for video storage
     * @return VideoStorageStrategy Configured video storage strategy
     */
    public function video(string $baseDirectory): VideoStorageStrategy
    {
        $rootDirectory = $this->getDocumentBaseDirectory($baseDirectory);

        return new VideoStorageStrategy($rootDirectory);
    }

    /**
     * Get all files for the document.
     *
     * @param  string|null  $identifier  Optional identifier (uses document's tenant if not provided)
     * @return array<string> Array of file paths within the document directory
     */
    public function getAllFiles(?string $identifier = null): array
    {
        $identifier ??= $this->document->getTenant()->getKey();

        return parent::getAllFiles($identifier);
    }

    /**
     * Get secure URL for a file requiring authentication.
     *
     * @param  string  $identifier  Optional identifier (uses document's tenant if not provided)
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(string $identifier, string $type, string $fileName): string
    {
        return parent::getSecureUrl($identifier, $type, $fileName);
    }

    /**
     * Get secure URL for a file requiring authentication (convenience method).
     *
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @return string Secure URL requiring authentication
     */
    public function getDocumentSecureUrl(string $type, string $fileName): string
    {
        return $this->getSecureUrl($this->document->getTenant()->getKey(), $type, $fileName);
    }

    /**
     * Get temporary URL for a file with expiration.
     *
     * @param  string  $identifier  The identifier for file access
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @param  int  $expiresIn  Expiration time in seconds
     * @return string Temporary URL with expiration
     */
    public function getTemporaryUrl(string $identifier, string $type, string $fileName, int $expiresIn = 1800): string
    {
        return parent::getTemporaryUrl($identifier, $type, $fileName, $expiresIn);
    }

    /**
     * Get temporary URL for a file with expiration (convenience method).
     *
     * @param  string  $type  The file type (images, videos, documents)
     * @param  string  $fileName  The filename
     * @param  int|null  $expiresIn  Expiration time in seconds (uses config default if null)
     * @return string Temporary URL with expiration
     */
    public function getDocumentTemporaryUrl(
        string $type,
        string $fileName,
        ?int $expiresIn = null,
    ): string {
        $expiresIn ??= config('directory-document.urls.default_expiry', 1800);

        return $this->getTemporaryUrl(
            $this->document->getTenant()->getKey(),
            $type,
            $fileName,
            $expiresIn,
        );
    }

    /**
     * Get the storage disk to use for document operations.
     *
     * Uses the directory-document configuration.
     *
     * @return string The storage disk name
     */
    protected function getDisk(): string
    {
        return config('directory-document.storage.disk', 'public');
    }

    /**
     * Get the base directory path for tenant-specific storage operations.
     *
     * Uses the configured hash strategy and base path for tenant isolation.
     *
     * @param  string|null  $identifier  The tenant identifier
     * @return string Base directory path (hashed tenant ID with base path)
     */
    protected function getBaseDirectory(?string $identifier = null): string
    {
        if ($identifier === null) {
            throw new InvalidArgumentException(
                'Tenant identifier is required for document storage operations.',
            );
        }

        $basePath = config('directory-document.storage.base_path', 'documents');
        $hashedId = LaraPath::base($identifier, SanitizationStrategy::HASHED);

        return $basePath ? "{$basePath}/{$hashedId}" : (string) $hashedId;
    }

    /**
     * Get the base directory path for document sub-folders.
     *
     * Creates secure paths using LaraPath with hashed tenant key as base.
     *
     * @param  string  $subDirectory  The sub-directory name (images, videos, etc.)
     * @return string Secure directory path for the sub-folder
     */
    private function getDocumentBaseDirectory(string $subDirectory): string
    {
        return LaraPath::base($this->document->getTenant()->getKey(), SanitizationStrategy::HASHED)
            ->add('documents')
            ->add($this->document->id, SanitizationStrategy::HASHED)
            ->add($subDirectory, SanitizationStrategy::SLUG)
            ->validate()
            ->toString();
    }
}
