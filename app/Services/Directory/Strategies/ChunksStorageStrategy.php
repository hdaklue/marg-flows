<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class ChunksStorageStrategy implements ChunksStorageStrategyContract
{
    private ?string $sessionId = null;

    public function __construct(private readonly string $tenantId) {}

    public function forSession(string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function store(UploadedFile $file): string
    {
        $this->validateConfiguration();

        $directory = $this->getDirectory();
        $filename = $this->generateChunkFilename();

        return $file->storeAs($directory, $filename);
    }

    public function getUrl(): string
    {
        throw new InvalidArgumentException('URL generation not supported for chunk files. Chunks are temporary storage.');
    }

    public function getDirectory(): string
    {
        $this->validateConfiguration();

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

    public function deleteSession(): bool
    {
        $this->validateConfiguration();
        $directory = $this->buildDirectory();

        return Storage::deleteDirectory($directory);
    }

    private function buildDirectory(): string
    {
        if (! $this->sessionId) {
            throw new Exception('Cannot build directory path: Session ID is required. Call forSession($sessionId) first.');
        }

        return "{$this->tenantId}/chunks/{$this->sessionId}";
    }

    private function generateChunkFilename(): string
    {
        $timestamp = time();
        $unique = uniqid();

        return "chunk_{$unique}_{$timestamp}";
    }

    private function validateConfiguration(): void
    {
        if (! $this->sessionId) {
            throw new Exception('Session ID is required. Call forSession($sessionId) first.');
        }
    }
}
