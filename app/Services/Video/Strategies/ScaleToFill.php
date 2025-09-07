<?php

declare(strict_types=1);

namespace App\Services\Video\Strategies;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\ValueObjects\Dimension;

class ScaleToFill implements ScaleStrategyContract
{
    public function __construct(
        private readonly Dimension $target,
    ) {}

    public function apply(Dimension $current, Dimension $_target): Dimension
    {
        $currentRatio = $current->getWidth() / $current->getHeight();
        $targetRatio = $this->target->getWidth() / $this->target->getHeight();

        if ($currentRatio > $targetRatio) {
            // Scale by height to fill
            $scaleFactor = $this->target->getHeight() / $current->getHeight();
        } else {
            // Scale by width to fill
            $scaleFactor = $this->target->getWidth() / $current->getWidth();
        }

        return $current->scaleByFactor($scaleFactor);
    }

    public function getDescription(): string
    {
        return "Scale to fill {$this->target} (may require cropping)";
    }

    public static function make(Dimension $target): self
    {
        return new self($target);
    }
}
