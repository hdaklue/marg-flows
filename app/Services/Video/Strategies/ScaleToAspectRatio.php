<?php

declare(strict_types=1);

namespace App\Services\Video\Strategies;

use App\Services\Video\Contracts\ScaleStrategyContract;
use App\Services\Video\ValueObjects\AspectRatio;
use App\Services\Video\ValueObjects\Dimension;

final class ScaleToAspectRatio implements ScaleStrategyContract
{
    public function __construct(
        private readonly AspectRatio $aspectRatio,
        private readonly null|int $maxWidth = null,
    ) {}

    public static function make(AspectRatio $aspectRatio, null|int $maxWidth = null): self
    {
        return new self($aspectRatio, $maxWidth);
    }

    public function apply(Dimension $current, Dimension $_target): Dimension
    {
        if ($this->maxWidth) {
            $dimensions = $this->aspectRatio->getOptimalDimensions($this->maxWidth);

            return Dimension::from($dimensions['width'], $dimensions['height']);
        }

        // Keep the larger dimension and calculate the other
        if ($current->isLandscape()) {
            $width = $current->getWidth();
            $height = (int) round($width / $this->aspectRatio->getRatio());
        } else {
            $height = $current->getHeight();
            $width = (int) round($height * $this->aspectRatio->getRatio());
        }

        return Dimension::from($width, $height);
    }

    public function getDescription(): string
    {
        $maxWidthText = $this->maxWidth ? " (max width: {$this->maxWidth}px)" : '';

        return "Scale to {$this->aspectRatio} aspect ratio{$maxWidthText}";
    }
}
