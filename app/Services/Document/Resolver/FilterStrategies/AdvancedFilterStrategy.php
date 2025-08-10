<?php

declare(strict_types=1);

namespace App\Services\Document\Resolver\FilterStrategies;

use App\Services\Document\Contracts\DocumentBlockFilterStrategy;
use App\Services\Document\DTOs\DocumentBlocksDto;

/**
 * Advanced plan filtering strategy
 * Allows all simple blocks plus images and video uploads.
 */
final class AdvancedFilterStrategy implements DocumentBlockFilterStrategy
{
    private const ALLOWED_BLOCKS = [
        'paragraph',
        'header',
        'images',
        'table',
        'nestedList',
        'alert',
        'hyperlink',
        'videoEmbed',
        'videoUpload',
    ];

    public function filter(DocumentBlocksDto $blocks, array $context = []): DocumentBlocksDto
    {
        return $blocks->applyBlockFilter(self::ALLOWED_BLOCKS);
    }

    public function getName(): string
    {
        return 'advanced';
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
