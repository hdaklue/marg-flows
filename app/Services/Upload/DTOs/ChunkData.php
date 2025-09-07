<?php

declare(strict_types=1);

namespace App\Services\Upload\DTOs;

final readonly class ChunkInfo
{
    public function __construct(
        public int $index,
        public int $size,
        public string $hash,
        public bool $uploaded = false,
    ) {}

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'size' => $this->size,
            'hash' => $this->hash,
            'uploaded' => $this->uploaded,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'],
            size: $data['size'],
            hash: $data['hash'],
            uploaded: $data['uploaded'] ?? false,
        );
    }
}

final readonly class ChunkData
{
    public function __construct(
        public string $sessionId,
        public string $fileType,
        public string $fileName,
        public int $totalSize,
        public int $chunkSize,
        /** @var ChunkInfo[] */
        public array $chunks,
        public null|string $mimeType = null,
        public null|array $metadata = null,
    ) {}

    public function getTotalChunks(): int
    {
        return count($this->chunks);
    }

    public function getCompletedChunks(): int
    {
        return count(array_filter(
            $this->chunks,
            fn(ChunkInfo $chunk) => $chunk->uploaded,
        ));
    }

    public function getUploadedBytes(): int
    {
        return array_sum(array_map(fn(ChunkInfo $chunk) => $chunk->uploaded
            ? $chunk->size
            : 0, $this->chunks));
    }

    public function getProgress(): float
    {
        if ($this->totalSize === 0) {
            return 0.0;
        }

        return round(($this->getUploadedBytes() / $this->totalSize) * 100, 2);
    }

    public function isComplete(): bool
    {
        return $this->getCompletedChunks() === $this->getTotalChunks();
    }

    public function getNextChunk(): null|ChunkInfo
    {
        foreach ($this->chunks as $chunk) {
            if (!$chunk->uploaded) {
                return $chunk;
            }
        }

        return null;
    }

    public function markChunkAsUploaded(int $chunkIndex): self
    {
        $updatedChunks = array_map(fn(ChunkInfo $chunk) => $chunk->index
        === $chunkIndex
            ? new ChunkInfo($chunk->index, $chunk->size, $chunk->hash, true)
            : $chunk, $this->chunks);

        return new self(
            sessionId: $this->sessionId,
            fileType: $this->fileType,
            fileName: $this->fileName,
            totalSize: $this->totalSize,
            chunkSize: $this->chunkSize,
            chunks: $updatedChunks,
            mimeType: $this->mimeType,
            metadata: $this->metadata,
        );
    }

    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'fileType' => $this->fileType,
            'fileName' => $this->fileName,
            'totalSize' => $this->totalSize,
            'chunkSize' => $this->chunkSize,
            'chunks' => array_map(
                fn(ChunkInfo $chunk) => $chunk->toArray(),
                $this->chunks,
            ),
            'mimeType' => $this->mimeType,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: $data['sessionId'],
            fileType: $data['fileType'],
            fileName: $data['fileName'],
            totalSize: $data['totalSize'],
            chunkSize: $data['chunkSize'],
            chunks: array_map(fn(array $chunk) => ChunkInfo::fromArray(
                $chunk,
            ), $data['chunks']),
            mimeType: $data['mimeType'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
