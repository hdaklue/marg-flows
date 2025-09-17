<?php

declare(strict_types=1);

namespace App\DTOs\EditorJS;

use App\Services\Document\ContentBlocks\BlocksCollection;
use BumpCore\EditorPhp\Block\Block;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class EditorJSDocumentDto extends ValidatedDTO
{
    public int $time;

    public mixed $blocks;

    public string $version;

    /**
     * Create a new document with blocks array.
     */
    public static function createWithBlocks(
        array $blocks,
        ?string $version = null,
    ): self {
        return self::fromArray([
            'time' => time(),
            'blocks' => $blocks,
            'version' => $version ?? config('editor.version', '2.28.2'),
        ]);
    }

    /**
     * Create from nested structure (current database format).
     */
    public static function fromNestedArray(array $data): self
    {
        // Handle the nested structure with top-level time/blocks/version
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            // Check if blocks contains the nested structure with inner blocks
            if (
                isset($data['blocks']['blocks'])
                && is_array($data['blocks']['blocks'])
            ) {
                // Use the inner blocks array
                return self::fromArray([
                    'time' => $data['time'] ?? time(),
                    'blocks' => $data['blocks']['blocks'], // Use the inner blocks
                    'version' => $data['version'] ?? config(
                        'editor.version',
                        '2.28.2',
                    ),
                ]);
            }

            // Direct blocks array
            return self::fromArray([
                'time' => $data['time'] ?? time(),
                'blocks' => $data['blocks'],
                'version' => $data['version'] ?? config(
                    'editor.version',
                    '2.28.2',
                ),
            ]);
        }

        // Handle flat structure (legacy format) - treat entire data as blocks
        return self::fromArray([
            'time' => time(),
            'blocks' => $data,
            'version' => config('editor.version', '2.28.2'),
        ]);
    }

    public function getBlocks(): BlocksCollection
    {
        if (is_array($this->blocks)) {
            return BlocksCollection::fromArray($this->blocks);
        }

        return $this->blocks instanceof BlocksCollection
            ? $this->blocks
            : BlocksCollection::empty();
    }

    /**
     * Check if document has any content blocks.
     */
    public function isEmpty(): bool
    {
        return $this->getBlocks()->isEmpty();
    }

    /**
     * Check if document has content (non-empty blocks).
     */
    public function hasContent(): bool
    {
        return $this->getBlocks()->hasNonEmptyBlocks();
    }

    /**
     * Get blocks as array for frontend consumption.
     */
    public function getBlocksAsArray(): array
    {
        return $this->getBlocks()->toArray();
    }

    /**
     * Get blocks as JSON string for frontend consumption.
     */
    public function getBlocksAsJson(): string
    {
        return $this->getBlocks()->toJson();
    }

    /**
     * Get the complete EditorJS format for frontend.
     */
    public function toEditorJSFormat(): array
    {
        return [
            'time' => $this->time,
            'blocks' => $this->getBlocks()->toArray(),
            'version' => $this->version,
        ];
    }

    /**
     * Create new document with updated blocks.
     */
    public function withBlocks(BlocksCollection $blocks): self
    {
        return self::fromArray([
            'time' => time(), // Update time when blocks change
            'blocks' => $blocks->toArray(),
            'version' => $this->version,
        ]);
    }

    /**
     * Create new document with updated time.
     */
    public function withUpdatedTime(): self
    {
        return self::fromArray([
            'time' => time(),
            'blocks' => $this->getBlocks()->toArray(),
            'version' => $this->version,
        ]);
    }

    /**
     * Get blocks by type.
     */
    public function getBlocksByType(string $type): BlocksCollection
    {
        return $this->getBlocks()->byType($type);
    }

    /**
     * Check if document has blocks of specific type.
     */
    public function hasBlockType(string $type): bool
    {
        return $this->getBlocks()->hasType($type);
    }

    /**
     * Get all block types present in document.
     */
    public function getBlockTypes(): array
    {
        return $this->getBlocks()->getTypes();
    }

    /**
     * Add a block to the document.
     */
    public function addBlock(Block $block): self
    {
        return $this->withBlocks($this->getBlocks()->add($block));
    }

    /**
     * Filter blocks by callback.
     */
    public function filterBlocks(callable $callback): self
    {
        return $this->withBlocks($this->getBlocks()->filter($callback));
    }

    protected function rules(): array
    {
        return [
            'time' => ['required', 'integer', 'min:0'],
            'blocks' => ['required', 'array'],
            'version' => ['required', 'string', 'min:1'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'time' => fn () => time(),
            'version' => fn () => config('editor.version', '2.28.2'),
            'blocks' => [],
        ];
    }

    protected function casts(): array
    {
        return [];
    }

    protected function afterValidation(): void
    {
        // Let getBlocks() handle the conversion when needed
    }
}
