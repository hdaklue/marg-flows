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

    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'],
            size: $data['size'],
            hash: $data['hash'],
            uploaded: $data['uploaded'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'size' => $this->size,
            'hash' => $this->hash,
            'uploaded' => $this->uploaded,
        ];
    }
}
