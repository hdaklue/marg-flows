<?php

declare(strict_types=1);

namespace App\DTOs\EditorJS;

use Illuminate\Support\Collection;
use InvalidArgumentException;

final class EditorJSBlocksCollection extends Collection
{
    /**
     * Create collection from array of block data
     */
    public static function fromArray(array $blocks): self
    {
        $blockDtos = [];
        
        foreach ($blocks as $blockData) {
            if (!is_array($blockData)) {
                throw new InvalidArgumentException('Each block must be an array');
            }
            
            $blockDtos[] = EditorJSBlockDto::fromArray($blockData);
        }

        return new static($blockDtos);
    }

    /**
     * Create empty collection
     */
    public static function empty(): self
    {
        return new static([]);
    }

    /**
     * Add item to collection with validation
     */
    public function add($item)
    {
        if (!$item instanceof EditorJSBlockDto) {
            throw new InvalidArgumentException('Item must be an instance of EditorJSBlockDto');
        }

        return parent::add($item);
    }

    /**
     * Push item to collection with validation
     */
    public function push(...$values)
    {
        foreach ($values as $value) {
            if (!$value instanceof EditorJSBlockDto) {
                throw new InvalidArgumentException('All items must be instances of EditorJSBlockDto');
            }
        }

        return parent::push(...$values);
    }

    /**
     * Put item in collection with validation
     */
    public function put($key, $value)
    {
        if (!$value instanceof EditorJSBlockDto) {
            throw new InvalidArgumentException('Value must be an instance of EditorJSBlockDto');
        }

        return parent::put($key, $value);
    }

    /**
     * Get blocks by type
     */
    public function byType(string $type): self
    {
        return $this->filter(fn(EditorJSBlockDto $block) => $block->isType($type));
    }

    /**
     * Check if collection has blocks of specific type
     */
    public function hasType(string $type): bool
    {
        return $this->byType($type)->isNotEmpty();
    }

    /**
     * Get all block types present in collection
     */
    public function getTypes(): array
    {
        return $this->map(fn(EditorJSBlockDto $block) => $block->type)
                   ->unique()
                   ->values()
                   ->toArray();
    }

    /**
     * Check if collection has any non-empty blocks
     */
    public function hasNonEmptyBlocks(): bool
    {
        return $this->filter(function($block) {
            if ($block instanceof EditorJSBlockDto) {
                return !$block->isEmpty();
            }
            // If somehow we get arrays, convert them
            if (is_array($block)) {
                $blockDto = EditorJSBlockDto::fromArray($block);
                return !$blockDto->isEmpty();
            }
            return false;
        })->isNotEmpty();
    }

    /**
     * Get only non-empty blocks
     */
    public function nonEmpty(): self
    {
        return $this->reject(fn(EditorJSBlockDto $block) => $block->isEmpty());
    }

    /**
     * Get blocks with text content
     */
    public function withText(): self
    {
        return $this->filter(fn(EditorJSBlockDto $block) => $block->hasText());
    }

    /**
     * Get all text content from blocks
     */
    public function extractText(): array
    {
        return $this->map(fn(EditorJSBlockDto $block) => $block->getText())
                   ->filter()
                   ->values()
                   ->toArray();
    }

    /**
     * Find block by ID
     */
    public function findById(string $id): ?EditorJSBlockDto
    {
        return $this->first(fn(EditorJSBlockDto $block) => $block->id === $id);
    }

    /**
     * Remove block by ID
     */
    public function removeById(string $id): self
    {
        return $this->reject(fn(EditorJSBlockDto $block) => $block->id === $id);
    }

    /**
     * Replace block by ID
     */
    public function replaceById(string $id, EditorJSBlockDto $newBlock): self
    {
        return $this->map(fn(EditorJSBlockDto $block) => $block->id === $id ? $newBlock : $block);
    }

    /**
     * Get blocks as array for JSON serialization
     */
    public function toArray(): array
    {
        return $this->map(function($block) {
            if ($block instanceof EditorJSBlockDto) {
                return $block->toArray();
            }
            // If it's already an array, ensure it has the required structure
            if (is_array($block)) {
                if (!isset($block['id'])) {
                    $block['id'] = bin2hex(random_bytes(5));
                }
                return $block;
            }
            // Fallback - should not happen
            return [];
        })->toArray();
    }

    /**
     * Get blocks as JSON string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Group blocks by type
     */
    public function groupByType(): Collection
    {
        return $this->groupBy(fn(EditorJSBlockDto $block) => $block->type);
    }

    /**
     * Count blocks by type
     */
    public function countByType(): Collection
    {
        return $this->groupByType()->map(fn($blocks) => $blocks->count());
    }

    /**
     * Sort blocks by type
     */
    public function sortByType(): self
    {
        return $this->sortBy(fn(EditorJSBlockDto $block) => $block->type);
    }

    /**
     * Get first block of specific type
     */
    public function firstOfType(string $type): ?EditorJSBlockDto
    {
        return $this->first(fn(EditorJSBlockDto $block) => $block->isType($type));
    }

    /**
     * Get last block of specific type
     */
    public function lastOfType(string $type): ?EditorJSBlockDto
    {
        return $this->last(fn(EditorJSBlockDto $block) => $block->isType($type));
    }

    /**
     * Check if all blocks are of specific type
     */
    public function allOfType(string $type): bool
    {
        return $this->every(fn(EditorJSBlockDto $block) => $block->isType($type));
    }

    /**
     * Apply callback to blocks of specific type
     */
    public function transformType(string $type, callable $callback): self
    {
        return $this->map(function(EditorJSBlockDto $block) use ($type, $callback) {
            return $block->isType($type) ? $callback($block) : $block;
        });
    }
}