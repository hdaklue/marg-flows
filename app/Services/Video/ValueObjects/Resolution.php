<?php

declare(strict_types=1);

namespace App\Services\Video\ValueObjects;

use App\Services\Video\Enums\BitrateEnum;

readonly class Resolution
{
    public function __construct(
        public Dimension $dimension,
        public AspectRatio $aspectRatio,
        public BitrateEnum $bitrate,
        public string $name,
        public string $qualityTier = 'medium'
    ) {}

    /**
     * Get dimension for a resolution based on orientation.
     * Base dimensions are assumed to be landscape (width > height).
     */
    private static function getDimensionForResolution(int $baseWidth, int $baseHeight, string $orientation): Dimension
    {
        return match ($orientation) {
            'portrait' => Dimension::from($baseHeight, $baseWidth), // Flip dimensions
            'square' => Dimension::from(min($baseWidth, $baseHeight), min($baseWidth, $baseHeight)), // Use smaller dimension
            default => Dimension::from($baseWidth, $baseHeight), // Keep as-is for 'landscape' and others
        };
    }

    public static function create144p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(256, 144, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::ULTRA_LOW_144P,
            name: '144p',
            qualityTier: 'ultra_low'
        );
    }

    public static function create240p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(426, 240, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::LOW_240P,
            name: '240p',
            qualityTier: 'low'
        );
    }

    public static function create360p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(640, 360, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::LOW_360P,
            name: '360p',
            qualityTier: 'low'
        );
    }

    public static function create480p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(854, 480, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::MEDIUM_480P,
            name: '480p',
            qualityTier: 'medium'
        );
    }

    public static function create720p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(1280, 720, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::HIGH_720P,
            name: '720p',
            qualityTier: 'high'
        );
    }

    public static function create1080p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(1920, 1080, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::HIGH_1080P,
            name: '1080p',
            qualityTier: 'high'
        );
    }

    public static function create1440p(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(2560, 1440, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::VERY_HIGH_1440P,
            name: '1440p',
            qualityTier: 'very_high'
        );
    }

    public static function create2K(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(2560, 1440, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::VERY_HIGH_2K,
            name: '2K',
            qualityTier: 'very_high'
        );
    }

    public static function create4K(string $orientation): self
    {
        $dimension = self::getDimensionForResolution(3840, 2160, $orientation);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::ULTRA_HIGH_4K,
            name: '4K',
            qualityTier: 'ultra_high'
        );
    }

    public static function createMobilePortrait(): self
    {
        $dimension = Dimension::from(1080, 1920);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::MOBILE_PORTRAIT,
            name: 'Mobile Portrait',
            qualityTier: 'mobile_optimized'
        );
    }

    public static function createMobileLandscape(): self
    {
        $dimension = Dimension::from(1920, 1080);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::MOBILE_LANDSCAPE,
            name: 'Mobile Landscape',
            qualityTier: 'mobile_optimized'
        );
    }

    public static function createMobileSquare(): self
    {
        $dimension = Dimension::from(1080, 1080);
        
        return new self(
            dimension: $dimension,
            aspectRatio: $dimension->getAspectRatio(),
            bitrate: BitrateEnum::MOBILE_PORTRAIT,
            name: 'Mobile Square',
            qualityTier: 'mobile_optimized'
        );
    }

    public function getPixelCount(): int
    {
        return $this->dimension->getPixelCount();
    }

    public function getBitrateKbps(): int
    {
        return $this->bitrate->getKbps();
    }

    public function getBitrateMbps(): float
    {
        return $this->bitrate->getMbps();
    }

    public function getWidth(): int
    {
        return $this->dimension->getWidth();
    }

    public function getHeight(): int
    {
        return $this->dimension->getHeight();
    }

    public function isLandscape(): bool
    {
        return $this->aspectRatio->isLandscape();
    }

    public function isPortrait(): bool
    {
        return $this->aspectRatio->isPortrait();
    }

    public function isSquare(): bool
    {
        return $this->aspectRatio->isSquare();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'pixels' => $this->getPixelCount(),
            'aspect_ratio' => $this->aspectRatio->toArray(),
            'bitrate_kbps' => $this->getBitrateKbps(),
            'bitrate_mbps' => $this->getBitrateMbps(),
            'quality_tier' => $this->qualityTier,
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%dx%d, %s, %d Kbps)',
            $this->name,
            $this->getWidth(),
            $this->getHeight(),
            $this->aspectRatio->getRatio(),
            $this->getBitrateKbps()
        );
    }
}