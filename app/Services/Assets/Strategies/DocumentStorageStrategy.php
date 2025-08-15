<?php

declare(strict_types=1);

namespace App\Services\Assets\Strategies;

use App\Services\Assets\Contracts\DocumentStorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class DocumentStorageStrategy implements DocumentStorageStrategyContract
{
    private ?string $tenantId = null;

    private ?string $documentId = null;

    private ?UploadedFile $file = null;

    private ?string $subdirectory = null;

    private ?string $storedPath = null;

    public function forTenant(string $tenantId): self
    {
        $this->tenantId = $tenantId;

        return $this;
    }

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
        if (! $this->tenantId) {
            throw new Exception('Cannot access documents directory: Tenant ID is required. Call forTenant($tenantId) first.');
        }
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
        if (! $this->tenantId) {
            throw new Exception('Cannot build directory path: Tenant ID is required. Call forTenant($tenantId) first.');
        }

        return $this->buildDirectory();
    }


    public function delete(string $path): bool
    {
        return Storage::delete($path);
    }

    private function buildDirectory(): string
    {
        if (! $this->tenantId) {
            throw new Exception('Cannot build directory path: Tenant ID is required. Call forTenant($tenantId) first.');
        }

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
