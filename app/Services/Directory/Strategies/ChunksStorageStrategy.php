<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Chunks Storage Strategy.
 *
 * Handles temporary chunk storage for file upload sessions with tenant isolation.
 * Each upload session creates its own subdirectory for chunk management.
 */
final class ChunksStorageStrategy extends BaseStorageStrategy implements
    ChunksStorageStrategyContract
{
    private null|string $sessionId = null;

    /**
     * Constructor receives the hashed tenant base directory from DirectoryManager.
     *
     * @param  string  $tenantBaseDirectory  The MD5-hashed tenant base directory
     */
    public function __construct(
        private readonly string $tenantBaseDirectory,
    ) {}

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
        throw new InvalidArgumentException(
            'URL generation not supported for chunk files. Chunks are temporary storage.',
        );
    }

    public function getDirectory(): string
    {
        $this->validateConfiguration();

        return $this->buildDirectory();
    }

    public function deleteSession(): bool
    {
        $this->validateConfiguration();
        $directory = $this->buildDirectory();

        return Storage::deleteDirectory($directory);
    }

    private function buildDirectory(): string
    {
        throw_unless(
            $this->sessionId,
            new Exception(
                'Cannot build directory path: Session ID is required. Call forSession($sessionId) first.',
            ),
        );

        return "{$this->tenantBaseDirectory}/chunks/{$this->sessionId}";
    }

    private function generateChunkFilename(): string
    {
        $timestamp = time();
        $unique = uniqid();

        return "chunk_{$unique}_{$timestamp}";
    }

    private function validateConfiguration(): void
    {
        throw_unless(
            $this->sessionId,
            new Exception(
                'Session ID is required. Call forSession($sessionId) first.',
            ),
        );
    }
}
