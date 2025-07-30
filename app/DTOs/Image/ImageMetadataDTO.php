<?php

declare(strict_types=1);

namespace App\DTOs\Image;

use WendellAdriel\ValidatedDTO\Casting\BooleanCast;
use WendellAdriel\ValidatedDTO\Casting\FloatCast;
use WendellAdriel\ValidatedDTO\Casting\IntegerCast;
use WendellAdriel\ValidatedDTO\Concerns\Wireable;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class ImageMetadataDTO extends ValidatedDTO
{
    use Wireable;

    public string $url;

    public bool $exists;

    public int $width;

    public int $height;

    public float $aspectRatio;

    public int $fileSizeBytes;

    public string $fileSizeHuman;

    public string $mimeType;

    public string $extension;

    public int $optimalContainerWidth;

    public int $optimalContainerHeight;

    public float $containerAspectRatio;

    public ?string $error;

    public bool $hasError;

    public float $maxZoomLevel;

    /**
     * Convert to array format optimized for JavaScript consumption
     */
    public function toJavaScriptFormat(): array
    {
        return [
            'url' => $this->url,
            'exists' => $this->exists,
            'dimensions' => [
                'width' => $this->width,
                'height' => $this->height,
                'aspectRatio' => $this->aspectRatio,
            ],
            'fileInfo' => [
                'sizeBytes' => $this->fileSizeBytes,
                'sizeHuman' => $this->fileSizeHuman,
                'mimeType' => $this->mimeType,
                'extension' => $this->extension,
            ],
            'container' => [
                'width' => $this->optimalContainerWidth,
                'height' => $this->optimalContainerHeight,
                'aspectRatio' => $this->containerAspectRatio,
            ],
            'maxZoomLevel' => $this->maxZoomLevel,
            'viewportBreakpoints' => $this->getViewportBreakpoints(),
            'error' => $this->error,
            'hasError' => $this->hasError,
        ];
    }

    /**
     * Get responsive viewport breakpoints with optimal dimensions
     */
    public function getViewportBreakpoints(): array
    {
        if (!$this->isValid()) {
            return [
                'mobile' => ['width' => 400, 'height' => 225],
                'tablet' => ['width' => 600, 'height' => 338],
                'desktop' => ['width' => 800, 'height' => 450],
            ];
        }

        return [
            'mobile' => $this->getOptimalDimensions(400, 300),
            'tablet' => $this->getOptimalDimensions(600, 400), 
            'desktop' => $this->getOptimalDimensions($this->optimalContainerWidth, $this->optimalContainerHeight),
        ];
    }

    /**
     * Check if image is valid and usable
     */
    public function isValid(): bool
    {
        return $this->exists && !$this->hasError && $this->width > 0 && $this->height > 0;
    }

    /**
     * Get optimal dimensions for a given container size while preserving aspect ratio
     */
    public function getOptimalDimensions(int $maxWidth, int $maxHeight): array
    {
        if (!$this->isValid()) {
            return ['width' => $maxWidth, 'height' => $maxHeight];
        }

        $scaleWidth = $maxWidth / $this->width;
        $scaleHeight = $maxHeight / $this->height;
        $scale = min($scaleWidth, $scaleHeight);

        return [
            'width' => (int)round($this->width * $scale),
            'height' => (int)round($this->height * $scale),
        ];
    }

    protected function casts(): array
    {
        return [
            'exists' => new BooleanCast(),
            'width' => new IntegerCast(),
            'height' => new IntegerCast(),
            'aspectRatio' => new FloatCast(),
            'fileSizeBytes' => new IntegerCast(),
            'optimalContainerWidth' => new IntegerCast(),
            'optimalContainerHeight' => new IntegerCast(),
            'containerAspectRatio' => new FloatCast(),
            'hasError' => new BooleanCast(),
            'maxZoomLevel' => new FloatCast(),
        ];
    }

    protected function rules(): array
    {
        return [
            'url' => ['required', 'string'],
            'exists' => ['required', 'boolean'],
            'width' => ['required', 'integer', 'min:0'],
            'height' => ['required', 'integer', 'min:0'],
            'aspectRatio' => ['required', 'numeric', 'min:0'],
            'fileSizeBytes' => ['required', 'integer', 'min:0'],
            'fileSizeHuman' => ['required', 'string'],
            'mimeType' => ['required', 'string'],
            'extension' => ['required', 'string'],
            'optimalContainerWidth' => ['required', 'integer', 'min:0'],
            'optimalContainerHeight' => ['required', 'integer', 'min:0'],
            'containerAspectRatio' => ['required', 'numeric', 'min:0'],
            'error' => ['nullable', 'string'],
            'hasError' => ['required', 'boolean'],
            'maxZoomLevel' => ['required', 'numeric', 'min:1', 'max:20'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'exists' => false,
            'width' => 0,
            'height' => 0,
            'aspectRatio' => 1.0,
            'fileSizeBytes' => 0,
            'fileSizeHuman' => '0 B',
            'mimeType' => 'application/octet-stream',
            'extension' => 'unknown',
            'optimalContainerWidth' => 400,
            'optimalContainerHeight' => 400,
            'containerAspectRatio' => 1.0,
            'error' => null,
            'hasError' => false,
        ];
    }
}