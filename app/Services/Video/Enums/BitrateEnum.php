<?php

declare(strict_types=1);

namespace App\Services\Video\Enums;

enum BitrateEnum: int
{
    case ULTRA_LOW_144P = 150;
    case LOW_240P = 300;
    case LOW_360P = 600;
    case MEDIUM_480P = 1000;
    case HIGH_720P = 2500;
    case HIGH_1080P = 4500;
    case VERY_HIGH_1440P = 8000;
    case VERY_HIGH_2K = 12000;
    case ULTRA_HIGH_4K = 15000;
    case MOBILE_PORTRAIT = 3500;
    case MOBILE_LANDSCAPE = 3000;

    public function getKbps(): int
    {
        return $this->value;
    }

    public function getMbps(): float
    {
        return round($this->value / 1000, 1);
    }

    public function getQualityTier(): string
    {
        return match ($this) {
            self::ULTRA_LOW_144P => 'ultra_low',
            self::LOW_240P, self::LOW_360P => 'low',
            self::MEDIUM_480P => 'medium',
            self::HIGH_720P, self::HIGH_1080P => 'high',
            self::VERY_HIGH_1440P, self::VERY_HIGH_2K => 'very_high',
            self::ULTRA_HIGH_4K => 'ultra_high',
            self::MOBILE_PORTRAIT, self::MOBILE_LANDSCAPE => 'mobile_optimized',
        };
    }

    /**
     * Calculate optimal bitrate based on pixels and target quality.
     */
    public static function calculateForPixels(
        int $pixels,
        string $qualityTier = 'medium',
    ): int {
        // Base bits per pixel (bpp) values for different quality tiers
        $bppValues = [
            'ultra_low' => 0.004,
            'low' => 0.0025,
            'medium' => 0.002,
            'high' => 0.002,
            'very_high' => 0.0022,
            'ultra_high' => 0.0018,
            'mobile_optimized' => 0.0017,
        ];

        $bpp = $bppValues[$qualityTier] ?? 0.002;

        // Calculate bitrate (assuming 30fps)
        $bitrate = (int) round(($pixels * $bpp * 30) / 1000);

        // Apply reasonable bounds
        return max(100, min(20000, $bitrate));
    }

    /**
     * Get bitrate recommendation for specific resolution.
     */
    public static function forResolution(string $resolution): self
    {
        return match (strtolower($resolution)) {
            '144p' => self::ULTRA_LOW_144P,
            '240p' => self::LOW_240P,
            '360p' => self::LOW_360P,
            '480p' => self::MEDIUM_480P,
            '720p' => self::HIGH_720P,
            '1080p' => self::HIGH_1080P,
            '1440p' => self::VERY_HIGH_1440P,
            '2k', '1440p+' => self::VERY_HIGH_2K,
            '4k', '2160p' => self::ULTRA_HIGH_4K,
            'mobile_portrait' => self::MOBILE_PORTRAIT,
            'mobile_landscape' => self::MOBILE_LANDSCAPE,
            default => self::HIGH_1080P,
        };
    }
}
