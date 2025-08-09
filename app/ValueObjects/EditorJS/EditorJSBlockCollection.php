<?php

declare(strict_types=1);

namespace App\ValueObjects\EditorJS;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Collection of EditorJS blocks
 * Handles block validation and manipulation
 */
final class EditorJSBlockCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var EditorJSBlock[] */
    private readonly array $blocks;

    /**
     * @param EditorJSBlock[] $blocks
     */
    public function __construct(array $blocks = [])
    {
        $this->validateBlocks($blocks);
        $this->blocks = array_values($blocks); // Reset array keys
    }

    public static function fromArray(array $data): self
    {
        $blocks = [];
        
        foreach ($data as $blockData) {
            if (!is_array($blockData)) {
                throw new InvalidArgumentException('Each block must be an array');
            }
            
            $blocks[] = EditorJSBlock::fromArray($blockData);
        }

        return new self($blocks);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return empty($this->blocks);
    }

    public function count(): int
    {
        return count($this->blocks);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->blocks);
    }

    public function toArray(): array
    {
        return array_map(
            fn(EditorJSBlock $block) => $block->toArray(),
            $this->blocks
        );
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get blocks by type
     */
    public function getBlocksByType(string $type): array
    {
        return array_filter(
            $this->blocks,
            fn(EditorJSBlock $block) => $block->getType() === $type
        );
    }

    /**
     * Check if collection has blocks of specific type
     */
    public function hasBlockType(string $type): bool
    {
        return !empty($this->getBlocksByType($type));
    }

    /**
     * Get all block types present in collection
     */
    public function getBlockTypes(): array
    {
        return array_unique(
            array_map(
                fn(EditorJSBlock $block) => $block->getType(),
                $this->blocks
            )
        );
    }

    /**
     * Add a block to the collection
     */
    public function add(EditorJSBlock $block): self
    {
        return new self([...$this->blocks, $block]);
    }

    /**
     * Filter blocks by callback
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->blocks, $callback));
    }

    /**
     * Map blocks to new collection
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->blocks));
    }

    private function validateBlocks(array $blocks): void
    {
        foreach ($blocks as $block) {
            if (!$block instanceof EditorJSBlock) {
                throw new InvalidArgumentException('All items must be EditorJSBlock instances');
            }
        }
    }
}