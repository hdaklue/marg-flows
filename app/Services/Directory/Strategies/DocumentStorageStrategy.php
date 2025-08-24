<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Document Storage Strategy.
 *
 * Handles document storage with tenant isolation, subdirectory organization,
 * and support for images, videos, and document types. Both tenant IDs and
 * document IDs are MD5 hashed for security and privacy.
 */
final class DocumentStorageStrategy extends BaseStorageStrategy implements DocumentStorageStrategyContract
{
    private ?string $documentId = null;

    private ?UploadedFile $file = null;

    private ?string $subdirectory = null;

    private ?string $storedPath = null;

    /**
     * Constructor receives the hashed tenant base directory from DirectoryManager.
     *
     * @param  string  $tenantBaseDirectory  The MD5-hashed tenant base directory
     */
    public function __construct(private readonly string $tenantBaseDirectory) {}

    /**
     * Set the document ID for this storage session.
     *
     * Document ID is MD5 hashed for security and privacy.
     *
     * @param  string  $documentId  The document identifier
     * @return self For method chaining
     */
    public function forDocument(string $documentId): self
    {
        $this->documentId = md5($documentId);

        return $this;
    }

    public function images(): ImageStorageStrategy
    {
        throw_unless($this->documentId, new Exception('Cannot access images directory: Document ID is required. Call forDocument($documentId) first.'));

        $baseDirectory = $this->buildDirectoryPath('images');

        return new ImageStorageStrategy($baseDirectory);
    }

    public function videos(): VideoStorageStrategy
    {
        throw_unless($this->documentId, new Exception('Cannot access videos directory: Document ID is required. Call forDocument($documentId) first.'));

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
        $filename = $this->generateFilename();

        $this->storedPath = $file->storeAs($directory, $filename);

        return $this->storedPath;
    }

    public function getUrl(): string
    {
        throw_unless($this->storedPath, new InvalidArgumentException('Cannot generate URL: File must be stored first. Call store($file) before getUrl().'));

        return Storage::url($this->storedPath);
    }

    public function getDirectory(): string
    {
        return $this->buildDirectory();
    }

    private function buildDirectory(): string
    {
        $parts = [$this->tenantBaseDirectory];

        if ($this->documentId) {
            $parts[] = "documents/{$this->documentId}";
        } else {
            $parts[] = 'documents';
        }

        if ($this->subdirectory) {
            $parts[] = $this->subdirectory;
        }

        return implode('/', $parts);
    }

    private function buildDirectoryPath(string $subdirectory): string
    {
        $parts = [$this->tenantBaseDirectory];

        if ($this->documentId) {
            $parts[] = "documents/{$this->documentId}";
        } else {
            $parts[] = 'documents';
        }

        $parts[] = $subdirectory;

        return implode('/', $parts);
    }

    private function generateFilename(): string
    {
        $extension = $this->file->extension();
        $timestamp = time();
        $unique = uniqid();

        return "{$unique}_{$timestamp}.{$extension}";
    }
}
