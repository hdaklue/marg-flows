<?php

declare(strict_types=1);

namespace App\Services\Document\BlocksDto;

use WendellAdriel\ValidatedDTO\SimpleDTO;

/**
 * Header block DTO for EditorJS.
 */
final class HeaderBlockDto extends SimpleDTO
{
    public string $id;

    public string $class = 'header';

    public array $data;

    public array $tunes = [];

    public function defaults(): array
    {
        return [

        ];
    }

    /**
     * Get header text.
     */
    public function getText(): string
    {
        return $this->data['text'] ?? '';
    }

    /**
     * Get header level (1-6).
     */
    public function getLevel(): int
    {
        return $this->data['level'] ?? 2;
    }

    /**
     * Check if header has content.
     */
    public function hasContent(): bool
    {
        return ! empty(trim(strip_tags($this->getText())));
    }

    /**
     * Get text content.
     */
    public function getTextContent(): string
    {
        return strip_tags($this->getText());
    }
}
