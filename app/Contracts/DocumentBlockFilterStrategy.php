<?php

declare(strict_types=1);

namespace App\Contracts;

use App\ValueObjects\EditorJS\EditorJSBlockCollection;

/**
 * Strategy contract for filtering document blocks based on different criteria
 */
interface DocumentBlockFilterStrategy
{
    /**
     * Filter blocks based on strategy implementation
     */
    public function filter(EditorJSBlockCollection $blocks, array $context = []): EditorJSBlockCollection;

    /**
     * Get the strategy name for identification
     */
    public function getName(): string;

    /**
     * Check if a block type is allowed by this strategy
     */
    public function isBlockTypeAllowed(string $blockType, array $context = []): bool;
}