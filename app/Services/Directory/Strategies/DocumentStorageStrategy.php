<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\DocumentStorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class DocumentStorageStrategy implements DocumentStorageStrategyContract
{
    private ?string $documentId = null;

    private ?UploadedFile $file = null;

    private ?string $subdirectory = null;

    private ?string $storedPath = null;

    public function __construct(private readonly string $tenantId) {}

    public function forDocument(string $documentId): self
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function images(): self
    {
        if (! $this->documentId) {
            throw new Exception('Cannot access images directory: Document ID is required. Call forDocument($documentId) first.');
        }
        $this->subdirectory = 'images';

        return $this;
    }

    public function videos(): self
    {
        if (! $this->documentId) {
            throw new Exception('Cannot access videos directory: Document ID is required. Call forDocument($documentId) first.');
        }
        $this->subdirectory = 'videos';

        return $this;
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
        if (! $this->storedPath) {
            throw new InvalidArgumentException('Cannot generate URL: File must be stored first. Call store($file) before getUrl().');
        }

        return Storage::url($this->storedPath);
    }

    public function getDirectory(): string
    {
        return $this->buildDirectory();
    }

    public function delete(string $fileName): bool
    {
        return Storage::delete($this->getDirectory() . "/{$fileName}");
    }

    public function get(string $fileName): ?string
    {
        return Storage::get($this->getDirectory() . "/{$fileName}");
    }

    public function getPath(string $fileName): ?string
    {
        $fullPath = $this->getDirectory() . "/{$fileName}";

        if (Storage::getDefaultDriver() === 'local') {
            return Storage::path($fullPath);
        }

        return $fullPath;
    }

    public function getFileUrl(string $fileName): string
    {
        return Storage::url($this->getDirectory() . "/{$fileName}");
    }

    private function buildDirectory(): string
    {
        $parts = [$this->tenantId];

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

    private function generateFilename(): string
    {
        $extension = $this->file->extension();
        $timestamp = time();
        $unique = uniqid();

        return "{$unique}_{$timestamp}.{$extension}";
    }
}
