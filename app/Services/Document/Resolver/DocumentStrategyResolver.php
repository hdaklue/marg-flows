<?php

declare(strict_types=1);

namespace App\Services\Document\Resolver;

use App\Services\Document\Contracts\DocumentBlockFilterStrategy;
use App\Services\Document\DTOs\DocumentBlocksDto;
use App\Services\Document\Resolver\FilterStrategies\AdvancedFilterStrategy;
use App\Services\Document\Resolver\FilterStrategies\SimpleFilterStrategy;
use App\Services\Document\Resolver\FilterStrategies\UltimateFilterStrategy;
use InvalidArgumentException;

/**
 * DocumentResolver manages filtering strategies for document blocks
 * Follows the Strategy pattern for scalable block filtering.
 */
final class DocumentStrategyResolver
{
    /** @var DocumentBlockFilterStrategy[] */
    private array $strategies = [];

    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    /**
     * Register a filtering strategy.
     */
    public function registerStrategy(DocumentBlockFilterStrategy $strategy): self
    {
        $this->strategies[$strategy->getName()] = $strategy;

        return $this;
    }

    /**
     * Filter blocks using the specified strategy.
     */
    public function filter(
        DocumentBlocksDto $blocks,
        string $strategyName,
        array $context = [],
    ): DocumentBlocksDto {
        $strategy = $this->getStrategy($strategyName);

        return $strategy->filter($blocks, $context);
    }

    /**
     * Get available strategy names.
     */
    public function getAvailableStrategies(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Check if a block type is allowed for a strategy.
     */
    public function isBlockTypeAllowed(
        string $blockType,
        string $strategyName,
        array $context = [],
    ): bool {
        $strategy = $this->getStrategy($strategyName);

        return $strategy->isBlockTypeAllowed($blockType, $context);
    }

    /**
     * Get allowed block types for a strategy.
     */
    public function getAllowedBlockTypes(string $strategyName): array
    {
        $strategy = $this->getStrategy($strategyName);

        return $strategy->getAllowedBlockTypes();
    }

    /**
     * Get strategy instance.
     */
    private function getStrategy(string $name): DocumentBlockFilterStrategy
    {
        throw_unless(
            isset($this->strategies[$name]),
            new InvalidArgumentException("Unknown filtering strategy: {$name}"),
        );

        return $this->strategies[$name];
    }

    /**
     * Register default filtering strategies.
     */
    private function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new SimpleFilterStrategy);
        $this->registerStrategy(new AdvancedFilterStrategy);
        $this->registerStrategy(new UltimateFilterStrategy);
    }
}
