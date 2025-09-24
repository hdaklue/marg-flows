<?php

declare(strict_types=1);

namespace App\Services\Video\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;

final class Dimension implements Arrayable, Jsonable
{
    private int $width;

    private int $height;

    public function __construct(int $width, int $height)
    {
        throw_if(
            $width <= 0 || $height <= 0,
            new InvalidArgumentException('Width and height should be positive integers'),
        );

        $this->width = $width;
        $this->height = $height;
    }

    public static function from(int $width, int $height): self
    {
        return new self($width, $height);
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getPixelCount(): int
    {
        return $this->width * $this->height;
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

    public function scaleTo(
        int $targetWidth,
        int $targetHeight,
        bool $maintainAspectRatio = true,
    ): self {
        throw_if(
            $targetWidth <= 0 || $targetHeight <= 0,
            new InvalidArgumentException('Target dimensions must be positive'),
        );

        if (!$maintainAspectRatio) {
            return new self($targetWidth, $targetHeight);
        }

        $currentRatio = $this->width / $this->height;
        $targetRatio = $targetWidth / $targetHeight;

        if ($currentRatio > $targetRatio) {
            // Constrained by width
            $scaledWidth = $targetWidth;
            $scaledHeight = (int) round($targetWidth / $currentRatio);
        } else {
            // Constrained by height
            $scaledHeight = $targetHeight;
            $scaledWidth = (int) round($targetHeight * $currentRatio);
        }

        return new self($scaledWidth, $scaledHeight);
    }

    public function scaleByFactor(float $factor): self
    {
        throw_if($factor <= 0, new InvalidArgumentException('Scale factor must be positive'));

        return new self((int) round($this->width * $factor), (int) round($this->height * $factor));
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->getAspectRatio()->getRatio(),
            'aspect_ratio_name' => $this->getAspectRatio()->getAspectRatio(),
            'pixel_count' => $this->getPixelCount(),
            'orientation' => $this->getOrientation(),
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function getAspectRatio(): AspectRatio
    {
        return AspectRatio::from($this->width, $this->height);
    }

    public function equals(self $other): bool
    {
        return $this->width === $other->width && $this->height === $other->height;
    }

    public function getOrientation(): string
    {
        if ($this->isSquare()) {
            return 'square';
        }

        return $this->isPortrait() ? 'portrait' : 'landscape';
    }

    public function __toString(): string
    {
        return "{$this->width}x{$this->height}";
    }
}
