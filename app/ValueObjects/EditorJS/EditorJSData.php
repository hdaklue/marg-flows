<?php

declare(strict_types=1);

namespace App\ValueObjects\EditorJS;

use InvalidArgumentException;

/**
 * Value object for EditorJS document data
 * Handles the complete EditorJS structure with metadata
 */
final class EditorJSData
{
    public function __construct(
        private readonly EditorJSBlockCollection $blocks,
        private readonly int $time,
        private readonly string $version,
    ) {
        $this->validateVersion();
    }

    public static function create(array $blocks = [], ?string $version = null): self
    {
        return new self(
            EditorJSBlockCollection::fromArray($blocks),
            time(),
            $version ?? self::getDefaultVersion(),
        );
    }

    public static function fromArray(array $data): self
    {
        // Handle nested structure (current format)
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            return new self(
                EditorJSBlockCollection::fromArray($data['blocks']),
                $data['time'] ?? time(),
                $data['version'] ?? self::getDefaultVersion(),
            );
        }

        // Handle flat structure (legacy format)
        return new self(
            EditorJSBlockCollection::fromArray($data),
            time(),
            self::getDefaultVersion(),
        );
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
        }

        return self::fromArray($data ?? []);
    }

    public static function empty(): self
    {
        return self::create();
    }

    public function getBlocks(): EditorJSBlockCollection
    {
        return $this->blocks;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function isEmpty(): bool
    {
        return $this->blocks->isEmpty();
    }

    public function getBlocksAsArray(): array
    {
        return $this->blocks->toArray();
    }

    public function getBlocksAsJson(): string
    {
        return $this->blocks->toJson();
    }

    public function withBlocks(array $blocks): self
    {
        return new self(
            EditorJSBlockCollection::fromArray($blocks),
            time(),
            $this->version,
        );
    }

    public function withUpdatedTime(): self
    {
        return new self(
            $this->blocks,
            time(),
            $this->version,
        );
    }

    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'blocks' => $this->blocks->toArray(),
            'version' => $this->version,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    private static function getDefaultVersion(): string
    {
        return config('editor.version', '2.28.2');
    }

    private function validateVersion(): void
    {
        if (empty($this->version)) {
            throw new InvalidArgumentException('EditorJS version cannot be empty');
        }
    }
}