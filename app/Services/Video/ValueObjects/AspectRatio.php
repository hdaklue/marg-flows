<?php

declare(strict_types=1);

namespace App\Services\Video\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable value object representing video aspect ratios with resolution support.
 */
final class AspectRatio implements JsonSerializable
{
    private const float DEFAULT_TOLERANCE = 0.02;

    /**
     * Standard aspect ratio mappings for video content.
     *
     * @var array<string, float>
     */
    private static array $map = [
        '1:1' => 1.00,
        '4:3' => 1.3333,
        '3:2' => 1.5,
        '16:10' => 1.6,
        '16:9' => 1.7778,
        '2:1' => 2.0,
        '2.39:1' => 2.39,
        '21:9' => 2.3333,
        '9:16' => 0.5625,
        '4:5' => 0.8,
        '2:3' => 0.6667,
        '5:4' => 1.25,
        '3:4' => 0.75,
        '1.91:1' => 1.91,
        '1.85:1' => 1.85,
        '3:1' => 3.0,
        '10:1' => 10.0,
    ];

    /**
     * Common video resolutions with their aspect ratios.
     *
     * @var array<array{name: string, width: int, height: int, label: string, ratio: float}>
     */
    private static array $resolutions = [
        // Standard HD/UHD resolutions
        [
            'name' => 'HD',
            'width' => 1280,
            'height' => 720,
            'label' => '16:9',
            'ratio' => 1.7778,
        ],
        [
            'name' => 'Full HD',
            'width' => 1920,
            'height' => 1080,
            'label' => '16:9',
            'ratio' => 1.7778,
        ],
        [
            'name' => 'QHD',
            'width' => 2560,
            'height' => 1440,
            'label' => '16:9',
            'ratio' => 1.7778,
        ],
        [
            'name' => '4K UHD',
            'width' => 3840,
            'height' => 2160,
            'label' => '16:9',
            'ratio' => 1.7778,
        ],
        [
            'name' => '8K UHD',
            'width' => 7680,
            'height' => 4320,
            'label' => '16:9',
            'ratio' => 1.7778,
        ],
        // Cinema resolutions
        [
            'name' => 'CinemaScope',
            'width' => 2048,
            'height' => 858,
            'label' => '2.39:1',
            'ratio' => 2.39,
        ],
        [
            'name' => 'DCI 4K',
            'width' => 4096,
            'height' => 1716,
            'label' => '2.39:1',
            'ratio' => 2.39,
        ],
        [
            'name' => 'DCI Flat',
            'width' => 1998,
            'height' => 1080,
            'label' => '1.85:1',
            'ratio' => 1.85,
        ],
        // Mobile/Social Media resolutions
        [
            'name' => 'Mobile Portrait',
            'width' => 1080,
            'height' => 1920,
            'label' => '9:16',
            'ratio' => 0.5625,
        ],
        [
            'name' => 'Instagram Portrait',
            'width' => 1080,
            'height' => 1350,
            'label' => '4:5',
            'ratio' => 0.8,
        ],
        [
            'name' => 'Instagram Square',
            'width' => 1080,
            'height' => 1080,
            'label' => '1:1',
            'ratio' => 1.0,
        ],
        // Traditional TV/Computer resolutions
        [
            'name' => 'SD 4:3',
            'width' => 640,
            'height' => 480,
            'label' => '4:3',
            'ratio' => 1.3333,
        ],
        [
            'name' => 'XGA',
            'width' => 1024,
            'height' => 768,
            'label' => '4:3',
            'ratio' => 1.3333,
        ],
        // Widescreen computer resolutions
        [
            'name' => 'WXGA+',
            'width' => 1440,
            'height' => 900,
            'label' => '16:10',
            'ratio' => 1.6,
        ],
        [
            'name' => 'WUXGA',
            'width' => 1920,
            'height' => 1200,
            'label' => '16:10',
            'ratio' => 1.6,
        ],
    ];

    /**
     * Cache for resolution lookups to improve performance.
     */
    private static array $resolutionCache = [];

    private function __construct(
        private readonly string $label,
        private readonly float $ratio,
        private readonly ?string $resolutionName = null,
        private readonly int $width = 0,
        private readonly int $height = 0,
    ) {
        throw_if(
            $this->width < 0 || $this->height < 0,
            new InvalidArgumentException(
                'Width and height cannot be negative.',
            ),
        );
        throw_if(
            $this->ratio <= 0,
            new InvalidArgumentException('Ratio must be positive.'),
        );
    }

    /**
     * Create an AspectRatio instance from width and height dimensions.
     */
    public static function from(
        float $width,
        float $height,
        float $tolerance = self::DEFAULT_TOLERANCE,
    ): ?self {
        throw_if(
            $width <= 0 || $height <= 0,
            new InvalidArgumentException(
                'Width and height must be positive non-zero values.',
            ),
        );

        $intWidth = (int) $width;
        $intHeight = (int) $height;
        $cacheKey = "{$intWidth}x{$intHeight}";

        // Check resolution cache first
        if (isset(self::$resolutionCache[$cacheKey])) {
            $res = self::$resolutionCache[$cacheKey];

            return new self(
                $res['label'],
                $res['ratio'],
                $res['name'],
                $intWidth,
                $intHeight,
            );
        }

        // Check exact resolution match
        foreach (self::$resolutions as $res) {
            if ($res['width'] === $intWidth && $res['height'] === $intHeight) {
                self::$resolutionCache[$cacheKey] = $res;

                return new self(
                    $res['label'],
                    $res['ratio'],
                    $res['name'],
                    $intWidth,
                    $intHeight,
                );
            }
        }

        // Check aspect ratio match with tolerance
        $actual = $width / $height;

        foreach (self::$map as $label => $targetRatio) {
            if (abs($actual - $targetRatio) < $tolerance) {
                return new self(
                    $label,
                    $targetRatio,
                    null,
                    $intWidth,
                    $intHeight,
                );
            }
        }

        return null;
    }

    /**
     * Create an AspectRatio instance from a Dimension object.
     */
    public static function fromDimension(
        Dimension $dimension,
        float $tolerance = self::DEFAULT_TOLERANCE,
    ): ?self {
        return self::from(
            $dimension->getWidth(),
            $dimension->getHeight(),
            $tolerance,
        );
    }

    /**
     * Create an AspectRatio instance from a ratio string (e.g., "16:9").
     */
    public static function fromString(string $ratioString): ?self
    {
        if (! isset(self::$map[$ratioString])) {
            return null;
        }

        return new self($ratioString, self::$map[$ratioString]);
    }

    /**
     * Create an AspectRatio instance from a decimal ratio value.
     */
    public static function fromRatio(
        float $ratio,
        float $tolerance = self::DEFAULT_TOLERANCE,
    ): ?self {
        throw_if(
            $ratio <= 0,
            new InvalidArgumentException('Ratio must be positive.'),
        );

        foreach (self::$map as $label => $targetRatio) {
            if (abs($ratio - $targetRatio) < $tolerance) {
                return new self($label, $targetRatio);
            }
        }

        return null;
    }

    /**
     * Get common video aspect ratios.
     */
    public static function getVideoAspectRatios(): array
    {
        return [
            '16:9' => 'Standard HD/UHD (YouTube, TV)',
            '9:16' => 'Mobile Portrait (TikTok, Instagram Stories)',
            '4:3' => 'Traditional TV',
            '1:1' => 'Square (Instagram Posts)',
            '2.39:1' => 'Cinema Widescreen',
            '21:9' => 'Ultra-wide Cinema',
            '4:5' => 'Instagram Portrait',
        ];
    }

    /**
     * Get all video resolutions grouped by aspect ratio.
     *
     * @return array<string, array<array{name: string, width: int, height: int, label: string, ratio: float}>>
     */
    public static function getAllResolutions(): array
    {
        $grouped = [];
        foreach (self::$resolutions as $resolution) {
            $label = $resolution['label'];
            if (! isset($grouped[$label])) {
                $grouped[$label] = [];
            }
            $grouped[$label][] = $resolution;
        }

        return $grouped;
    }

    /**
     * Get standard video quality resolutions (HD, Full HD, 4K, etc.).
     *
     * @return array<array{name: string, width: int, height: int, label: string, ratio: float}>
     */
    public static function getStandardVideoResolutions(): array
    {
        return array_filter(
            self::$resolutions,
            fn ($res) => in_array($res['name'], [
                'HD',
                'Full HD',
                'QHD',
                '4K UHD',
                '8K UHD',
            ]),
        );
    }

    /**
     * Get mobile/social media optimized resolutions.
     *
     * @return array<array{name: string, width: int, height: int, label: string, ratio: float}>
     */
    public static function getMobileResolutions(): array
    {
        return array_filter(
            self::$resolutions,
            fn ($res) => (
                str_contains($res['name'], 'Mobile')
                || str_contains($res['name'], 'Instagram')
            ),
        );
    }

    /**
     * Get cinema/film industry resolutions.
     *
     * @return array<array{name: string, width: int, height: int, label: string, ratio: float}>
     */
    public static function getCinemaResolutions(): array
    {
        return array_filter(
            self::$resolutions,
            fn ($res) => (
                str_contains($res['name'], 'Cinema')
                || str_contains($res['name'], 'DCI')
            ),
        );
    }

    public function getAspectRatio(): string
    {
        return $this->label;
    }

    public function getRatio(): float
    {
        return $this->ratio;
    }

    public function getResolutionName(): ?string
    {
        return $this->resolutionName;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    public function isLandscape(): bool
    {
        return $this->width >= $this->height;
    }

    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    public function isWidescreen(): bool
    {
        return $this->ratio >= 1.6;
    }

    public function isCinematic(): bool
    {
        return $this->ratio >= 2.0;
    }

    /**
     * Check if this aspect ratio equals another.
     */
    public function equals(self $other): bool
    {
        return
            $this->label === $other->label
            && abs($this->ratio - $other->ratio) < 1e-10;
    }

    /**
     * Calculate scaled dimensions maintaining aspect ratio.
     */
    public function scaleTo(int $targetWidth, int $targetHeight): array
    {
        throw_if(
            $targetWidth <= 0 || $targetHeight <= 0,
            new InvalidArgumentException('Target dimensions must be positive.'),
        );

        $targetRatio = $targetWidth / $targetHeight;

        if ($this->ratio > $targetRatio) {
            // Constrained by width
            $scaledWidth = $targetWidth;
            $scaledHeight = (int) round($targetWidth / $this->ratio);
        } else {
            // Constrained by height
            $scaledHeight = $targetHeight;
            $scaledWidth = (int) round($targetHeight * $this->ratio);
        }

        return [
            'width' => $scaledWidth,
            'height' => $scaledHeight,
        ];
    }

    /**
     * Get optimal dimensions for a given max width while maintaining aspect ratio.
     */
    public function getOptimalDimensions(int $maxWidth): array
    {
        $height = (int) round($maxWidth / $this->ratio);

        return [
            'width' => $maxWidth,
            'height' => $height,
        ];
    }

    /**
     * Get all available resolutions for this aspect ratio.
     *
     * @return array<array{name: string, width: int, height: int, label: string, ratio: float}>
     */
    public function getResolutions(): array
    {
        return array_filter(
            self::$resolutions,
            fn ($res) => (
                abs($res['ratio'] - $this->ratio) < self::DEFAULT_TOLERANCE
            ),
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'aspect_ratio' => $this->label,
            'ratio' => $this->ratio,
            'resolution' => $this->resolutionName,
            'width' => $this->width,
            'height' => $this->height,
            'orientation' => $this->getOrientation(),
            'is_widescreen' => $this->isWidescreen(),
            'is_cinematic' => $this->isCinematic(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the orientation as a string.
     */
    private function getOrientation(): string
    {
        if ($this->isSquare()) {
            return 'square';
        }

        return $this->isPortrait() ? 'portrait' : 'landscape';
    }

    /**
     * Get string representation of the aspect ratio.
     */
    public function __toString(): string
    {
        return $this->label;
    }
}
