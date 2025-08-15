<?php

declare(strict_types=1);

namespace App\Services\Assets\Strategies;

use App\Services\Assets\Contracts\ChunksStorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class ChunksStorageStrategy implements ChunksStorageStrategyContract
{
    private ?string $tenantId = null;
    private ?string $sessionId = null;

    public function forTenant(string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

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


    public function delete(string $path): bool
    {
        return Storage::delete($path);
    }

    public function deleteSession(): bool
    {
        $this->validateConfiguration();
        $directory = $this->buildDirectory();
        return Storage::deleteDirectory($directory);
    }

    private function buildDirectory(): string
    {
        if (!$this->tenantId) {
            throw new Exception('Cannot build directory path: Tenant ID is required. Call forTenant($tenantId) first.');
        }

        if (!$this->sessionId) {
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
        if (!$this->tenantId) {
            throw new Exception('Tenant ID is required. Call forTenant($tenantId) first.');
        }

        if (!$this->sessionId) {
            throw new Exception('Session ID is required. Call forSession($sessionId) first.');
        }
    }
}