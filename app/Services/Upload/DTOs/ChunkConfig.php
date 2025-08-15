<?php

declare(strict_types=1);

namespace App\Services\Upload\DTOs;

final readonly class ChunkConfig
{
    public function __construct(
        public int $maxFileSize,
        public int $chunkSize,
        public bool $useChunkedUpload,
        public int $maxConcurrentUploads = 3,
        public int $retryAttempts = 3,
        public int $timeoutSeconds = 300 // 5 minutes
    ) {}

    public function toArray(): array
    {
        return [
            'maxFileSize' => $this->maxFileSize,
            'chunkSize' => $this->chunkSize,
            'useChunkedUpload' => $this->useChunkedUpload,
            'maxConcurrentUploads' => $this->maxConcurrentUploads,
            'retryAttempts' => $this->retryAttempts,
            'timeoutSeconds' => $this->timeoutSeconds,
        ];
    }

    public function toArrayForFrontend(): array
    {
        return [
            'maxFileSize' => $this->maxFileSize,
            'chunkSize' => $this->chunkSize,
            'useChunkedUpload' => $this->useChunkedUpload,
            'maxConcurrentUploads' => $this->maxConcurrentUploads,
            'retryAttempts' => $this->retryAttempts,
            'timeoutSeconds' => $this->timeoutSeconds,
            // Human-readable sizes for frontend
            'maxFileSizeMB' => round($this->maxFileSize / (1024 * 1024), 2),
            'chunkSizeMB' => round($this->chunkSize / (1024 * 1024), 2),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            maxFileSize: $data['maxFileSize'],
            chunkSize: $data['chunkSize'],
            useChunkedUpload: $data['useChunkedUpload'],
            maxConcurrentUploads: $data['maxConcurrentUploads'] ?? 3,
            retryAttempts: $data['retryAttempts'] ?? 3,
            timeoutSeconds: $data['timeoutSeconds'] ?? 300
        );
    }
}