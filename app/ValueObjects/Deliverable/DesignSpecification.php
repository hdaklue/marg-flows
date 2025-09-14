<?php

declare(strict_types=1);

namespace App\ValueObjects\Deliverable;

use App\Contracts\Deliverables\DeliverableSpecification;
use App\ValueObjects\Dimension\Dimension;

/**
 * ValueObject for design deliverable specifications.
 */
final class DesignSpecification implements DeliverableSpecification
{
    private const string TYPE = 'design';

    public Dimension $dimensions;

    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly array $safeArea,
        private readonly array $constraints,
        private readonly array $requirements,
    ) {
        $this->dimensions = Dimension::from($this->width, $this->height);
    }

    public static function fromConfig(array $config): self
    {
        return new self(
            width: $config['width'] ?? 0,
            height: $config['height'] ?? 0,

            safeArea: $config['safe_area'] ?? [],
            constraints: $config['constraints'] ?? [],
            requirements: $config['requirements'] ?? [],
        );
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getAspectRatio(): float
    {
        return $this->dimensions->getAspectRatio()->getRatio();
    }

    public function getAspectRatioName(): string
    {
        return $this->dimensions->getAspectRatio()->getAspectRatio();
    }

    public function getSafeArea(): array
    {
        return $this->safeArea;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getSafeAreaPixels(): array
    {
        $safeArea = $this->safeArea;
        $result = [];

        if (isset($safeArea['top'])) {
            $result['top'] = $safeArea['top'];
        } elseif (isset($safeArea['top_percentage'])) {
            $result['top'] = (int) (
                $this->height * $safeArea['top_percentage']
            );
        }

        if (isset($safeArea['bottom'])) {
            $result['bottom'] = $safeArea['bottom'];
        } elseif (isset($safeArea['bottom_percentage'])) {
            $result['bottom'] = (int) (
                $this->height * $safeArea['bottom_percentage']
            );
        }

        if (isset($safeArea['left'])) {
            $result['left'] = $safeArea['left'];
        } elseif (isset($safeArea['left_percentage'])) {
            $result['left'] = (int) (
                $this->width * $safeArea['left_percentage']
            );
        }

        if (isset($safeArea['right'])) {
            $result['right'] = $safeArea['right'];
        } elseif (isset($safeArea['right_percentage'])) {
            $result['right'] = (int) (
                $this->width * $safeArea['right_percentage']
            );
        }

        return $result;
    }

    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    public function isLandscape(): bool
    {
        return $this->width > $this->height;
    }

    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    public function getOrientation(): string
    {
        if ($this->isSquare()) {
            return 'square';
        }

        return $this->isPortrait() ? 'portrait' : 'landscape';
    }

    public function validate(array $fileData): bool
    {
        // Validate file dimensions if provided
        if (isset($fileData['width'], $fileData['height'])) {
            $fileWidth = (int) $fileData['width'];
            $fileHeight = (int) $fileData['height'];

            // Check if dimensions match exactly
            if ($fileWidth === $this->width && $fileHeight === $this->height) {
                return true;
            }

            // Check if aspect ratio matches with tolerance
            $fileRatio = $fileWidth / $fileHeight;
            $tolerance = 0.02; // 2% tolerance

            if (abs($fileRatio - $this->getAspectRatio()) <= $tolerance) {
                return true;
            }

            return false;
        }

        // If no dimensions provided, assume valid
        return true;
    }

    public function getValidationRules(): array
    {
        return [
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
            'format' => [
                'required',
                'string',
                'in:png,jpg,jpeg,svg,gif,webp,ai,psd,sketch,fig',
            ],
        ];
    }

    public function matchesDimensions(int $width, int $height): bool
    {
        return $this->width === $width && $this->height === $height;
    }

    public function matchesAspectRatio(
        float $ratio,
        float $tolerance = 0.02,
    ): bool {
        return abs($this->getAspectRatio() - $ratio) <= $tolerance;
    }

    public function scaleToFit(int $maxWidth, int $maxHeight): array
    {
        $scale = min($maxWidth / $this->width, $maxHeight / $this->height);

        return [
            'width' => (int) ($this->width * $scale),
            'height' => (int) ($this->height * $scale),
            'scale' => $scale,
        ];
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->getAspectRatio(),
            'aspect_ratio_name' => $this->getAspectRatioName(),
            'safe_area' => $this->safeArea,
            'constraints' => $this->constraints,
            'requirements' => $this->requirements,
            'orientation' => $this->getOrientation(),
            'safe_area_pixels' => $this->getSafeAreaPixels(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function equals(self $other): bool
    {
        return
            $this->width === $other->width
            && $this->height === $other->height
            && abs($this->getAspectRatio() - $other->getAspectRatio()) < 1e-10;
    }
}
