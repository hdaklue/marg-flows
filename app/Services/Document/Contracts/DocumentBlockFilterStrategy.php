<?php

declare(strict_types=1);

namespace App\Services\Document\Contracts;

use App\Services\Document\DTOs\DocumentBlocksDto;

/**
 * Strategy contract for filtering document blocks based on different criteria.
 */
interface DocumentBlockFilterStrategy
{
    /**
     * Filter blocks based on strategy implementation.
     */
    public function filter(DocumentBlocksDto $blocks, array $context = []): DocumentBlocksDto;

    /**
     * Get the strategy name for identification.
     */
    public function getName(): string;

    /**
     * Check if a block type is allowed by this strategy.
     */
    public function isBlockTypeAllowed(string $blockType, array $context = []): bool;

    /**
     * Get allowed block types for this strategy.
     */
    public function getAllowedBlockTypes(): array;
}
