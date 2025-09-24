<?php

declare(strict_types=1);

namespace App\Services\Upload\DTOs;

final readonly class ProgressData
{
    public function __construct(
        public int $completedChunks,
        public int $totalChunks,
        public int $bytesUploaded,
        public int $totalBytes,
        public float $percentage,
        public string $status = 'uploading', // uploading, completed, error
        public null|array $currentChunk = null,
        public null|int $estimatedTimeRemaining = null,
        public null|string $error = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            completedChunks: $data['completedChunks'],
            totalChunks: $data['totalChunks'],
            bytesUploaded: $data['bytesUploaded'],
            totalBytes: $data['totalBytes'],
            percentage: $data['percentage'],
            status: $data['status'] ?? 'uploading',
            currentChunk: $data['currentChunk'] ?? null,
            estimatedTimeRemaining: $data['estimatedTimeRemaining'] ?? null,
            error: $data['error'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'completedChunks' => $this->completedChunks,
            'totalChunks' => $this->totalChunks,
            'bytesUploaded' => $this->bytesUploaded,
            'totalBytes' => $this->totalBytes,
            'percentage' => $this->percentage,
            'status' => $this->status,
            'currentChunk' => $this->currentChunk,
            'estimatedTimeRemaining' => $this->estimatedTimeRemaining,
            'error' => $this->error,
        ];
    }
}
