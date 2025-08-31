<?php

declare(strict_types=1);

namespace App\Services\Document\Resolver\FilterStrategies;

use App\Services\Document\Contracts\DocumentBlockFilterStrategy;
use App\Services\Document\DTOs\DocumentBlocksDto;

/**
 * Simple plan filtering strategy
 * Allows basic text and formatting blocks only.
 */
final class SimpleFilterStrategy implements DocumentBlockFilterStrategy
{
    private const array ALLOWED_BLOCKS = [
        'paragraph',
        'header',
        'table',
        'nestedList',
        'alert',
        'linkTool',
        'videoEmbed', // Embed only, no uploads
    ];

    public function filter(DocumentBlocksDto $blocks, array $context = []): DocumentBlocksDto
    {

        return $blocks->applyBlockFilter(self::ALLOWED_BLOCKS);
    }

    public function getName(): string
    {
        return 'simple';
    }

    public function isBlockTypeAllowed(string $blockType, array $context = []): bool
    {
        return in_array($blockType, self::ALLOWED_BLOCKS, true);
    }

    public function getAllowedBlockTypes(): array
    {
        return self::ALLOWED_BLOCKS;
    }
}
