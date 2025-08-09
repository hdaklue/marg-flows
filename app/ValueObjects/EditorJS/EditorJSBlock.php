<?php

declare(strict_types=1);

namespace App\ValueObjects\EditorJS;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Value object for individual EditorJS block
 * Handles block structure and validation
 */
final class EditorJSBlock implements JsonSerializable
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly array $data,
        private readonly ?array $tunes = null,
    ) {
        $this->validateBlock();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? self::generateId(),
            type: $data['type'] ?? throw new InvalidArgumentException('Block type is required'),
            data: $data['data'] ?? [],
            tunes: $data['tunes'] ?? null,
        );
    }

    public static function create(string $type, array $data = [], ?string $id = null, ?array $tunes = null): self
    {
        return new self(
            id: $id ?? self::generateId(),
            type: $type,
            data: $data,
            tunes: $tunes,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTunes(): ?array
    {
        return $this->tunes;
    }

    public function isEmpty(): bool
    {
        return empty($this->data) || $this->isEmptyByType();
    }

    public function withData(array $data): self
    {
        return new self($this->id, $this->type, $data, $this->tunes);
    }

    public function withTunes(?array $tunes): self
    {
        return new self($this->id, $this->type, $this->data, $tunes);
    }

    public function toArray(): array
    {
        $array = [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
        ];

        if ($this->tunes !== null) {
            $array['tunes'] = $this->tunes;
        }

        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function generateId(): string
    {
        return bin2hex(random_bytes(5));
    }

    private function validateBlock(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException('Block ID cannot be empty');
        }

        if (empty($this->type)) {
            throw new InvalidArgumentException('Block type cannot be empty');
        }

        if (!is_array($this->data)) {
            throw new InvalidArgumentException('Block data must be an array');
        }

        if ($this->tunes !== null && !is_array($this->tunes)) {
            throw new InvalidArgumentException('Block tunes must be an array or null');
        }
    }

    /**
     * Check if block is empty based on its type
     */
    private function isEmptyByType(): bool
    {
        return match ($this->type) {
            'paragraph' => empty(trim($this->data['text'] ?? '')),
            'header' => empty(trim($this->data['text'] ?? '')),
            'list' => empty($this->data['items'] ?? []),
            'checklist' => empty($this->data['items'] ?? []),
            'quote' => empty(trim($this->data['text'] ?? '')),
            'code' => empty(trim($this->data['code'] ?? '')),
            'delimiter', 'raw' => false, // These are never considered empty
            'table' => empty($this->data['content'] ?? []),
            'image', 'attaches' => empty($this->data['file'] ?? []),
            default => false, // Unknown types are not considered empty
        };
    }
}