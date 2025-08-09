<?php

declare(strict_types=1);

namespace App\Services\Document\BlocksDto;

use WendellAdriel\ValidatedDTO\SimpleDTO;

/**
 * Paragraph block DTO for EditorJS.
 */
final class ParagraphBlockDto extends SimpleDTO
{
    public string $id;

    public string $type = 'paragraph';

    public array $data;

    public array $tunes = [];

    /**
     * Get paragraph text.
     */
    public function getText(): string
    {
        return $this->data['text'] ?? '';
    }

    /**
     * Check if paragraph has content.
     */
    public function hasContent(): bool
    {
        return ! empty(trim(strip_tags($this->getText())));
    }

    /**
     * Get text content (same as getText for paragraphs).
     */
    public function getTextContent(): string
    {
        return strip_tags($this->getText());
    }
}
