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
            'maxZoomLevel' => $this->maxZoomLevel,
            'error' => $this->error,
            'hasError' => $this->hasError,
        ];
    }


    /**
     * Check if image is valid and usable
     */
    public function isValid(): bool
    {
        return $this->exists && !$this->hasError && $this->width > 0 && $this->height > 0;
    }


    protected function casts(): array
    {
        return [
            'exists' => new BooleanCast(),
            'width' => new IntegerCast(),
            'height' => new IntegerCast(),
            'aspectRatio' => new FloatCast(),
            'fileSizeBytes' => new IntegerCast(),
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
            'error' => null,
            'hasError' => false,
        ];
    }
}