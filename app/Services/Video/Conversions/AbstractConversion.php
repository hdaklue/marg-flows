<?php

declare(strict_types=1);

namespace App\Services\Video\Conversions;

use App\Services\Video\Contracts\ConversionContract;
use App\Services\Video\ValueObjects\Dimension;
use ProtoneMedia\LaravelFFMpeg\Exporters\MediaExporter;

abstract class AbstractConversion implements ConversionContract
{
    protected string $format = 'mp4';
    protected string $quality = 'medium';
    protected ?Dimension $dimension = null;
    protected ?int $bitrate = null;
    protected bool $allowScaleUp = false;
    protected ?Dimension $maxDimension = null;
    protected ?Dimension $minDimension = null;
    protected bool $maintainAspectRatio = true;
    protected array $constraints = [];

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getQuality(): string
    {
        return $this->quality;
    }

    public function getDimension(): ?Dimension
    {
        return $this->dimension;
    }

    public function getTargetBitrate(): ?int
    {
        return $this->bitrate;
    }

    public function allowScaleUp(): bool
    {
        return $this->allowScaleUp;
    }

    public function getMaxDimension(): ?Dimension
    {
        return $this->maxDimension;
    }

    public function getMinDimension(): ?Dimension
    {
        return $this->minDimension;
    }

    public function shouldMaintainAspectRatio(): bool
    {
        return $this->maintainAspectRatio;
    }

    public function getConstraints(): array
    {
        return array_merge([
            'allow_scale_up' => $this->allowScaleUp,
            'max_dimension' => $this->maxDimension?->toArray(),
            'min_dimension' => $this->minDimension?->toArray(),
            'maintain_aspect_ratio' => $this->maintainAspectRatio,
        ], $this->constraints);
    }

    public function getType(): string
    {
        return 'conversion';
    }

    /**
     * Check if the current dimension should be converted based on constraints.
     */
    protected function shouldConvert(Dimension $currentDimension): bool
    {
        $targetDimension = $this->getDimension();
        
        if (!$targetDimension) {
            return true;
        }

        // Check if scale up is needed and allowed
        $currentPixels = $currentDimension->getPixelCount();
        $targetPixels = $targetDimension->getPixelCount();
        
        // If target has more pixels than current, it's a scale up
        if ($targetPixels > $currentPixels && !$this->allowScaleUp) {
            return false;
        }

        // Check max dimension constraints
        if ($this->maxDimension) {
            if ($targetDimension->getWidth() > $this->maxDimension->getWidth() ||
                $targetDimension->getHeight() > $this->maxDimension->getHeight()) {
                return false;
            }
        }

        // Check min dimension constraints
        if ($this->minDimension) {
            if ($targetDimension->getWidth() < $this->minDimension->getWidth() ||
                $targetDimension->getHeight() < $this->minDimension->getHeight()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate the final dimension considering constraints and current video dimensions.
     */
    public function calculateFinalDimension(Dimension $currentDimension): ?Dimension
    {
        $targetDimension = $this->getDimension();
        
        if (!$targetDimension || !$this->shouldConvert($currentDimension)) {
            return $currentDimension;
        }

        // Apply max dimension constraints
        if ($this->maxDimension) {
            $maxWidth = $this->maxDimension->getWidth();
            $maxHeight = $this->maxDimension->getHeight();
            
            if ($targetDimension->getWidth() > $maxWidth || $targetDimension->getHeight() > $maxHeight) {
                $targetDimension = $targetDimension->scaleTo($maxWidth, $maxHeight, $this->maintainAspectRatio);
            }
        }

        // Apply min dimension constraints
        if ($this->minDimension) {
            $minWidth = $this->minDimension->getWidth();
            $minHeight = $this->minDimension->getHeight();
            
            if ($targetDimension->getWidth() < $minWidth || $targetDimension->getHeight() < $minHeight) {
                // Scale up to meet minimum requirements only if allowed
                if ($this->allowScaleUp) {
                    $scaleFactorWidth = $minWidth / $targetDimension->getWidth();
                    $scaleFactorHeight = $minHeight / $targetDimension->getHeight();
                    $scaleFactor = max($scaleFactorWidth, $scaleFactorHeight);
                    
                    $targetDimension = $targetDimension->scaleByFactor($scaleFactor);
                }
            }
        }

        return $targetDimension;
    }

    /**
     * Check if this conversion would require scaling up from the current dimension.
     */
    public function wouldScaleUp(Dimension $currentDimension): bool
    {
        $targetDimension = $this->getDimension();
        
        if (!$targetDimension) {
            return false;
        }

        return $targetDimension->getPixelCount() > $currentDimension->getPixelCount();
    }
}