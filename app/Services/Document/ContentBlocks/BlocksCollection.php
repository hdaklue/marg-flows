<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class BlocksCollection extends Collection
{
    /**
     * Create collection from array of block data.
     */
    public static function fromArray(array $blocks): self
    {
        $editorData = [
            'time' => now()->timestamp * 1000,
            'blocks' => $blocks,
            'version' => '2.28.2',
        ];

        $editor = new EditorPhp(json_encode($editorData));

        return new self($editor->blocks->all());
    }

    /**
     * Create empty collection.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Add item to collection with validation.
     */
    public function add($item)
    {
        throw_unless(
            $item instanceof Block,
            new InvalidArgumentException('Item must be an instance of Block'),
        );

        return parent::add($item);
    }

    /**
     * Push item to collection with validation.
     */
    public function push(...$values)
    {
        foreach ($values as $value) {
            throw_unless(
                $value instanceof Block,
                new InvalidArgumentException('All items must be instances of Block'),
            );
        }

        return parent::push(...$values);
    }

    /**
     * Put item in collection with validation.
     */
    public function put($key, $value)
    {
        throw_unless(
            $value instanceof Block,
            new InvalidArgumentException('Value must be an instance of Block'),
        );

        return parent::put($key, $value);
    }

    /**
     * Get blocks by type.
     */
    public function byType(string $type): self
    {
        return $this->filter(fn (Block $block) => $block->type === $type);
    }

    /**
     * Check if collection has blocks of specific type.
     */
    public function hasType(string $type): bool
    {
        return $this->byType($type)->isNotEmpty();
    }

    /**
     * Get all block types present in collection.
     */
    public function getTypes(): array
    {
        $types = [];
        foreach ($this->items as $block) {
            if ($block instanceof Block) {
                $types[] = $block->type;
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * Check if collection has any non-empty blocks.
     */
    public function hasNonEmptyBlocks(): bool
    {
        return $this->filter(function (Block $block) {
            return $this->isBlockNonEmpty($block);
        })->isNotEmpty();
    }

    /**
     * Get only non-empty blocks.
     */
    public function nonEmpty(): self
    {
        return $this->filter(fn (Block $block) => $this->isBlockNonEmpty($block));
    }

    /**
     * Get blocks with text content.
     */
    public function withText(): self
    {
        return $this->filter(fn (Block $block) => $this->hasText($block));
    }

    /**
     * Get all text content from blocks.
     */
    public function extractText(): array
    {
        return $this->map(fn (Block $block) => $this->getText($block))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Find block by type and data criteria.
     */
    public function findByData(string $key, mixed $value): ?Block
    {
        return $this->first(fn (Block $block) => $block->get($key) === $value);
    }

    /**
     * Remove blocks by type and data criteria.
     */
    public function removeByData(string $key, mixed $value): self
    {
        return $this->reject(fn (Block $block) => $block->get($key) === $value);
    }

    /**
     * Replace blocks by type and data criteria.
     */
    public function replaceByData(string $key, mixed $value, Block $newBlock): self
    {
        return $this->map(fn (Block $block) => $block->get($key) === $value ? $newBlock : $block);
    }

    /**
     * Get blocks as array for JSON serialization.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->items as $block) {
            if ($block instanceof Block) {
                $result[] = $block->toArray();
            }
        }

        return $result;
    }

    /**
     * Get blocks as JSON string.
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Group blocks by type.
     */
    public function groupByType(): Collection
    {
        return $this->groupBy(fn (Block $block) => $block->type);
    }

    /**
     * Count blocks by type.
     */
    public function countByType(): Collection
    {
        return $this->groupByType()->map(fn ($blocks) => $blocks->count());
    }

    /**
     * Sort blocks by type.
     */
    public function sortByType(): self
    {
        return $this->sortBy(fn (Block $block) => $block->type);
    }

    /**
     * Get first block of specific type.
     */
    public function firstOfType(string $type): ?Block
    {
        return $this->first(fn (Block $block) => $block->type === $type);
    }

    /**
     * Get last block of specific type.
     */
    public function lastOfType(string $type): ?Block
    {
        return $this->last(fn (Block $block) => $block->type === $type);
    }

    /**
     * Check if all blocks are of specific type.
     */
    public function allOfType(string $type): bool
    {
        return $this->every(fn (Block $block) => $block->type === $type);
    }

    /**
     * Apply callback to blocks of specific type.
     */
    public function transformType(string $type, callable $callback): self
    {
        return $this->map(function (Block $block) use ($type, $callback) {
            return $block->type === $type ? $callback($block) : $block;
        });
    }

    /**
     * Check if a block is non-empty based on its type and data.
     */
    private function isBlockNonEmpty(Block $block): bool
    {
        return match ($block->type) {
            'paragraph' => ! empty(trim($block->get('text', ''))),
            'header' => ! empty(trim($block->get('text', ''))),
            'list' => ! empty($block->get('items', [])),
            'checklist' => ! empty($block->get('items', [])),
            'quote' => ! empty(trim($block->get('text', ''))),
            'code' => ! empty(trim($block->get('code', ''))),
            'delimiter', 'raw' => true,
            'table' => ! empty($block->get('content', [])),
            'image', 'attaches' => ! empty($block->get('file', [])),
            default => true,
        };
    }

    /**
     * Check if a block has text content.
     */
    private function hasText(Block $block): bool
    {
        $text = $this->getText($block);

        return ! empty(trim($text ?? ''));
    }

    /**
     * Get text content from a block.
     */
    private function getText(Block $block): ?string
    {
        return match ($block->type) {
            'paragraph', 'header', 'quote' => $block->get('text'),
            'code' => $block->get('code'),
            default => null,
        };
    }
}
