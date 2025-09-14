<?php

declare(strict_types=1);

namespace App\Services\Document\Collections;

use Exception;
use Illuminate\Support\Collection;

/**
 * Collection for handling EditorJS blocks with utility methods.
 */
final class DocumentBlocksCollection extends Collection
{
    /**
     * Create from EditorJS data structure preserving metadata.
     */
    public static function fromEditorJS(array $data): self
    {
        // Handle nested structure with time/blocks/version
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            // Extract inner blocks if double nested

            return new self($data['blocks']);
        }
        throw new Exception('Unable to resolve data');
    }

    public function filterBlocks(array $allowedBlocks): self
    {
        $this->reject(fn ($item): bool => in_array(
            $item['type'],
            $allowedBlocks,
        ));

        return $this;
    }

    /**
     * Get blocks as JSON string for frontend consumption.
     */
    public function toEditorJson(): string
    {
        $editorData = [
            'time' => $this->time, // EditorJS expects milliseconds
            'blocks' => $this->toArray(),
            'version' => $this->version,
        ];

        return json_encode($editorData, JSON_THROW_ON_ERROR);
    }

    /**
     * Get blocks formatted for database storage (nested structure).
     */
    public function toDatabaseFormat(): array
    {
        return [
            'time' => time(),
            'blocks' => [
                'time' => time() * 1000,
                'blocks' => $this->toArray(),
                'version' => '2.31.0-rc.7',
            ],
            'version' => '2.28.2',
        ];
    }

    /**
     * Check if collection has any meaningful content.
     */
    public function hasContent(): bool
    {
        return $this->isNotEmpty() && $this->hasNonEmptyBlocks();
    }

    /**
     * Check if collection has non-empty blocks.
     */
    public function hasNonEmptyBlocks(): bool
    {
        return $this->contains(function ($block) {
            if (! is_array($block) || ! isset($block['type'], $block['data'])) {
                return false;
            }

            $data = $block['data'];

            return match ($block['type']) {
                'paragraph' => ! empty($data['text']),
                'header' => ! empty($data['text']),
                'list' => ! empty($data['items']),
                'table' => ! empty($data['content']),
                'image', 'images' => ! empty($data['file']['url'])
                    || ! empty($data['files']),
                'video' => ! empty($data['file']['url']),
                'embed' => ! empty($data['source']),
                'quote' => ! empty($data['text']),
                'alert' => ! empty($data['message']),
                default => ! empty($data),
            };
        });
    }

    /**
     * Get blocks filtered by type.
     */
    public function byType(string $type): self
    {
        return $this->filter(
            fn ($block) => (
                is_array($block)
                && isset($block['type'])
                && $block['type'] === $type
            ),
        );
    }

    /**
     * Check if collection has blocks of specific type.
     */
    public function hasType(string $type): bool
    {
        return $this->byType($type)->isNotEmpty();
    }

    /**
     * Get text content from all text-based blocks.
     */
    public function getTextContent(): string
    {
        return $this->map(function ($block) {
            if (! is_array($block) || ! isset($block['type'], $block['data'])) {
                return '';
            }

            return match ($block['type']) {
                'paragraph', 'header' => $block['data']['text'] ?? '',
                'quote' => $block['data']['text'] ?? '',
                'alert' => $block['data']['message'] ?? '',
                'list' => implode(' ', $block['data']['items'] ?? []),
                default => '',
            };
        })->filter()->implode(' ');
    }
}
