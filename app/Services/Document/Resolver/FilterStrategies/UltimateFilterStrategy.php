<?php

declare(strict_types=1);

namespace App\Services\Document\Resolver\FilterStrategies;

use App\Services\Document\Contracts\DocumentBlockFilterStrategy;
use App\Services\Document\DTOs\DocumentBlocksDto;

/**
 * Ultimate plan filtering strategy
 * Allows all block types - no filtering.
 */
final class UltimateFilterStrategy implements DocumentBlockFilterStrategy
{
    public function filter(
        DocumentBlocksDto $blocks,
        array $context = [],
    ): DocumentBlocksDto {
        return $blocks; // No filtering - all blocks allowed
    }

    public function getName(): string
    {
        return 'ultimate';
    }

    public function isBlockTypeAllowed(
        string $blockType,
        array $context = [],
    ): bool {
        return true; // All block types allowed
    }

    public function getAllowedBlockTypes(): array
    {
        return []; // Empty array means all types allowed
    }
}
