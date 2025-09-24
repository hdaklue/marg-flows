<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\ChunksStorageStrategyContract;
use Exception;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Chunks Storage Strategy.
 *
 * Handles temporary chunk storage for file upload sessions with tenant isolation.
 * Uses LaraPath for secure directory structure and hashed tenant/session IDs.
 */
final class ChunksStorageStrategy extends BaseStorageStrategy implements
    ChunksStorageStrategyContract
{
    private null|string $sessionId = null;

    /**
     * Constructor receives the tenant ID for secure path building.
     *
     * @param  string  $tenantId  The tenant identifier (will be hashed for security)
     */
    public function __construct(
        private readonly string $tenantId,
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
        $filename = $this->generateSecureChunkFilename();

        return $file->storeAs($directory, $filename);
    }

    public function getUrl(): string
    {
        throw new InvalidArgumentException(
            'URL generation not supported for chunk files. Chunks are temporary storage.',
        );
    }

    public function getSecureUrl(
        string $route,
        string $fileName,
        string $tenantId,
        string $type,
    ): string {
        throw new InvalidArgumentException(
            'Secure URL generation not supported for chunk files. Chunks are temporary storage.',
        );
    }

    public function getDirectory(): string
    {
        $this->validateConfiguration();

        return $this->buildSecureDirectory();
    }

    public function deleteSession(): bool
    {
        $this->validateConfiguration();
        $directory = $this->buildSecureDirectory();

        return Storage::deleteDirectory($directory);
    }

    /**
     * Build secure directory path using LaraPath.
     *
     * @return string Secure directory path for chunks
     */
    private function buildSecureDirectory(): string
    {
        throw_unless(
            $this->sessionId,
            new Exception(
                'Cannot build directory path: Session ID is required. Call forSession($sessionId) first.',
            ),
        );

        return PathBuilder::base('tenants')
            ->add($this->tenantId, SanitizationStrategy::HASHED)
            ->add('chunks')
            ->add($this->sessionId, SanitizationStrategy::HASHED)
            ->validate()
            ->toString();
    }

    /**
     * Generate secure chunk filename using LaraPath sanitization.
     *
     * @return string Secure chunk filename
     */
    private function generateSecureChunkFilename(): string
    {
        $timestamp = time();
        $unique = uniqid();
        $filename = "chunk_{$unique}_{$timestamp}";

        return PathBuilder::base('')->add($filename, SanitizationStrategy::SLUG)->toString();
    }

    private function validateConfiguration(): void
    {
        throw_unless(
            $this->sessionId,
            new Exception('Session ID is required. Call forSession($sessionId) first.'),
        );
    }
}
