<?php

declare(strict_types=1);

namespace App\ValueObjects\Image;

use InvalidArgumentException;
use JsonSerializable;

final class AspectRatio implements JsonSerializable
{
    // ===== STATIC MAPPINGS =====

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

    private static array $resolutions =
        [
            ['name' => 'HD', 'width' => 1280, 'height' => 720,  'label' => '16:9', 'ratio' => 1.7778],
            ['name' => 'Full HD', 'width' => 1920, 'height' => 1080, 'label' => '16:9', 'ratio' => 1.7778],
            ['name' => 'QHD', 'width' => 2560, 'height' => 1440, 'label' => '16:9', 'ratio' => 1.7778],
            ['name' => '4K UHD', 'width' => 3840, 'height' => 2160, 'label' => '16:9', 'ratio' => 1.7778],
            ['name' => '8K UHD', 'width' => 7680, 'height' => 4320, 'label' => '16:9', 'ratio' => 1.7778],
            ['name' => 'WXGA', 'width' => 1366, 'height' => 768,  'label' => '16:9', 'ratio' => 1.7778],

            ['name' => 'XGA', 'width' => 1024, 'height' => 768,  'label' => '4:3',  'ratio' => 1.3333],
            ['name' => 'SXGA+', 'width' => 1400, 'height' => 1050, 'label' => '4:3',  'ratio' => 1.3333],

            ['name' => 'WXGA+', 'width' => 1440, 'height' => 900,  'label' => '16:10', 'ratio' => 1.6],
            ['name' => 'WSXGA+', 'width' => 1680, 'height' => 1050, 'label' => '16:10', 'ratio' => 1.6],
            ['name' => 'WUXGA', 'width' => 1920, 'height' => 1200, 'label' => '16:10', 'ratio' => 1.6],

            ['name' => 'Mobile Portrait', 'width' => 1080, 'height' => 1920, 'label' => '9:16',  'ratio' => 0.5625],
            ['name' => 'Instagram Portrait', 'width' => 1080, 'height' => 1350, 'label' => '4:5',  'ratio' => 0.8],

            ['name' => 'CinemaScope', 'width' => 2048, 'height' => 858, 'label' => '2.39:1', 'ratio' => 2.39],
            ['name' => 'DCI 4K', 'width' => 4096, 'height' => 1716, 'label' => '2.39:1', 'ratio' => 2.39],
            ['name' => 'DCI Flat', 'width' => 1998, 'height' => 1080, 'label' => '1.85:1', 'ratio' => 1.85],
        ];

    private function __construct(
        private readonly string $label,
        private readonly float $ratio,
        private readonly ?string $resolutionName = null,
        private readonly int $width = 0,
        private readonly int $height = 0,
    ) {}

    public static function from(float $width, float $height): ?self
    {
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Width and height must be positive non-zero values.');
        }

        $intWidth = (int) $width;
        $intHeight = (int) $height;

        foreach (self::$resolutions as $res) {
            if ($res['width'] === $intWidth && $res['height'] === $intHeight) {
                return new self($res['label'], $res['ratio'], $res['name'], $intWidth, $intHeight);
            }
        }

        $actual = $width / $height;
        $tolerance = 0.02;

        foreach (self::$map as $label => $targetRatio) {
            if (abs($actual - $targetRatio) < $tolerance) {
                return new self($label, $targetRatio, null, $intWidth, $intHeight);
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

    public function toArray(): array
    {
        return [
            'aspect_ratio' => $this->label,
            'ratio' => $this->ratio,
            'resolution' => $this->resolutionName,
            'width' => $this->width,
            'height' => $this->height,
            'orientation' => $this->isPortrait() ? 'portrait' : 'landscape',
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
