<?php

declare(strict_types=1);

namespace App\Services\Document\DTOs;

use WendellAdriel\ValidatedDTO\SimpleDTO;

/**
 * General Block DTO for all EditorJS block types
 * Structure: { "id": "...", "type": "...", "data": {...} }.
 */
final class BlockDto extends SimpleDTO
{
    public string $id;

    public string $type;

    public array $data;

    public static function fromBlock(array $block): static
    {
        return self::fromArray([
            'id' => $block['id'] ?? '',
            'type' => $block['type'] ?? '',
            'data' => $block['data'] ?? [],
        ]);
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }

    /**
     * Check if block has specific data field.
     */
    public function hasDataField(string $field): bool
    {
        return isset($this->data[$field]);
    }

    /**
     * Get specific data field with default.
     */
    public function getDataField(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }

    protected function defaults(): array
    {
        return [
            'id' => '',
            'type' => '',
            'data' => [],
        ];
    }

    protected function casts(): array
    {
        return [];
    }
}
