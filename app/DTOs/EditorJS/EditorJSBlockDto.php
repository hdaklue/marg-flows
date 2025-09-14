<?php

declare(strict_types=1);

namespace App\DTOs\EditorJS;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class EditorJSBlockDto extends ValidatedDTO
{
    public string $id;

    public string $type;

    public array $data;

    public ?array $tunes;

    /**
     * Check if block is empty based on its type.
     */
    public function isEmpty(): bool
    {
        return empty($this->data) || $this->isEmptyByType();
    }

    /**
     * Get text content from the block if it has any.
     */
    public function getText(): ?string
    {
        return match ($this->type) {
            'paragraph', 'header', 'quote' => $this->data['text'] ?? null,
            'code' => $this->data['code'] ?? null,
            default => null,
        };
    }

    /**
     * Check if block has text content.
     */
    public function hasText(): bool
    {
        return ! empty(trim($this->getText() ?? ''));
    }

    /**
     * Get block level for header blocks.
     */
    public function getLevel(): ?int
    {
        if ($this->type === 'header') {
            return $this->data['level'] ?? null;
        }

        return null;
    }

    /**
     * Get block ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Check if block is of specific type.
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Create new block with updated data.
     */
    public function withData(array $data): self
    {
        return self::fromArray([
            'id' => $this->id,
            'type' => $this->type,
            'data' => $data,
            'tunes' => $this->tunes,
        ]);
    }

    /**
     * Create new block with updated tunes.
     */
    public function withTunes(?array $tunes): self
    {
        return self::fromArray([
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
            'tunes' => $tunes,
        ]);
    }

    /**
     * Convert to array for JSON serialization.
     */
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

    protected function rules(): array
    {
        return [
            'id' => ['sometimes', 'string', 'min:1'],
            'type' => ['required', 'string', 'min:1'],
            'data' => ['required', 'array'],
            'tunes' => ['sometimes', 'array'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'id' => $this->generateId(),
            'tunes' => null,
        ];
    }

    protected function casts(): array
    {
        return [];
    }

    /**
     * Generate a random block ID.
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(5));
    }

    /**
     * Check if block is empty based on its type.
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
