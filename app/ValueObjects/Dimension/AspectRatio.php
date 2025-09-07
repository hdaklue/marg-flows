<?php

declare(strict_types=1);

namespace App\ValueObjects\Dimension;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable value object representing image aspect ratios with resolution support.
 */
final class AspectRatio implements JsonSerializable
{
    private const float DEFAULT_TOLERANCE = 0.02;

    /**
     * Standard aspect ratio mappings.
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
     * Common screen resolutions with their aspect ratios.
     *
     * @var array<array{name: string, width: int, height: int, label: string, ratio: float}>
     */
    private static array $resolutions = [
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
        [
            'name' => 'WXGA',
            'width' => 1366,
            'height' => 768,
            'label' => '16:9',
            'ratio' => 1.7778,
        ],
        [
            'name' => 'XGA',
            'width' => 1024,
            'height' => 768,
            'label' => '4:3',
            'ratio' => 1.3333,
        ],
        [
            'name' => 'SXGA+',
            'width' => 1400,
            'height' => 1050,
            'label' => '4:3',
            'ratio' => 1.3333,
        ],
        [
            'name' => 'WXGA+',
            'width' => 1440,
            'height' => 900,
            'label' => '16:10',
            'ratio' => 1.6,
        ],
        [
            'name' => 'WSXGA+',
            'width' => 1680,
            'height' => 1050,
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
    ];

    /**
     * Cache for resolution lookups to improve performance.
     */
    private static array $resolutionCache = [];

    private function __construct(
        private readonly string $label,
        private readonly float $ratio,
        private readonly null|string $resolutionName = null,
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
    ): null|self {
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
     * Create an AspectRatio instance from a ratio string (e.g., "16:9").
     */
    public static function fromString(string $ratioString): null|self
    {
        if (!isset(self::$map[$ratioString])) {
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
    ): null|self {
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

    public function getAspectRatio(): string
    {
        return $this->label;
    }

    public function getRatio(): float
    {
        return $this->ratio;
    }

    public function getResolutionName(): null|string
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

    /**
     * Check if this aspect ratio equals another.
     */
    public function equals(self $other): bool
    {
        return (
            $this->label === $other->label
            && abs($this->ratio - $other->ratio) < 1e-10
        );
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
