<?php

declare(strict_types=1);

namespace App\Services\Directory\Examples;

use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use App\Services\Directory\Exceptions\DirectoryException;
use App\Services\Directory\Strategies\BaseStorageStrategy;
use App\Services\Directory\Strategies\ImageStorageStrategy;
use App\Services\Directory\Strategies\VideoStorageStrategy;
use App\Services\Directory\Utils\FilenameGenerator;
use Exception;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Improved Document Storage Strategy.
 *
 * Example implementation showing all improvements applied:
 * - File facade usage
 * - Utility classes for paths and filenames
 * - Better error handling
 * - Consistent naming and structure
 */
final class ImprovedDocumentStorageStrategy extends BaseStorageStrategy implements
    DocumentStorageStrategyContract
{
    private null|string $documentId = null;

    private null|UploadedFile $file = null;

    private null|string $subdirectory = null;

    private null|string $storedPath = null;

    public function __construct(
        private readonly string $tenantBaseDirectory,
    ) {}

    /**
     * Set the document ID for this storage session.
     * Uses PathBuilder for secure directory name generation.
     */
    public function forDocument(string $documentId): self
    {
        $this->documentId = hash('md5', $documentId);

        return $this;
    }

    public function images(): ImageStorageStrategy
    {
        throw_unless(
            $this->documentId,
            DirectoryException::configurationError(
                'Document ID is required. Call forDocument($documentId) first.',
            ),
        );

        $baseDirectory = $this->buildDirectoryPath('images');

        return new ImageStorageStrategy($baseDirectory);
    }

    public function videos(): VideoStorageStrategy
    {
        throw_unless(
            $this->documentId,
            DirectoryException::configurationError(
                'Document ID is required. Call forDocument($documentId) first.',
            ),
        );

        $baseDirectory = $this->buildDirectoryPath('videos');

        return new VideoStorageStrategy($baseDirectory);
    }

    public function documents(): self
    {
        $this->subdirectory = 'documents';

        return $this;
    }

    public function store(UploadedFile $file): string
    {
        $this->file = $file;

        $directory = $this->buildDirectory();
        $filename = FilenameGenerator::generateFromUpload($file);

        try {
            $this->storedPath = $file->storeAs($directory, $filename);

            return $this->storedPath;
        } catch (Exception $e) {
            throw DirectoryException::storageError('file upload', $e->getMessage());
        }
    }

    public function getUrl(): string
    {
        throw_unless(
            $this->storedPath,
            DirectoryException::configurationError(
                'File must be stored first. Call store($file) before getUrl().',
            ),
        );

        return Storage::url($this->storedPath);
    }

    public function getDirectory(): string
    {
        return $this->buildDirectory();
    }

    /**
     * Build directory path using PathBuilder utility.
     */
    private function buildDirectory(): string
    {
        $segments = [$this->tenantBaseDirectory];

        if ($this->documentId) {
            $segments[] = 'documents';
            $segments[] = $this->documentId;
        } else {
            $segments[] = 'documents';
        }

        if ($this->subdirectory) {
            $segments[] = $this->subdirectory;
        }

        return PathBuilder::base($segments[0])->add(...array_slice($segments, 1))->toString();
    }

    /**
     * Build directory path for specific subdirectory types.
     */
    private function buildDirectoryPath(string $subdirectory): string
    {
        $segments = [$this->tenantBaseDirectory];

        if ($this->documentId) {
            $segments[] = 'documents';
            $segments[] = $this->documentId;
        } else {
            $segments[] = 'documents';
        }

        $segments[] = $subdirectory;

        return PathBuilder::base($segments[0])->add(...array_slice($segments, 1))->toString();
    }
}
