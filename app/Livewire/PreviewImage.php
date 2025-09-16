<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DTOs\Image\ImageMetadataDTO;
use App\Services\Image\ImageMetadataService;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class PreviewImage extends Component
{
    public string $image;

    public array $comments = [];

    public int $maxContainerWidth = 800;

    public int $maxContainerHeight = 600;

    private ?ImageMetadataDTO $imageMetadata = null;

    public function mount(
        ?string $image = null,
        array $comments = [],
        int $maxContainerWidth = 800,
        int $maxContainerHeight = 600,
    ): void {
        $this->image = $image ?: asset('img/1.jpeg');
        $this->maxContainerWidth = $maxContainerWidth;
        $this->maxContainerHeight = $maxContainerHeight;
        $this->comments = $comments ?: $this->getDefaultComments();
    }

    #[Computed]
    public function imageMetadata(): ImageMetadataDTO
    {
        if ($this->imageMetadata === null) {
            $service = app(ImageMetadataService::class);
            $this->imageMetadata = $service->extractMetadata(
                $this->image,
                $this->maxContainerWidth,
                $this->maxContainerHeight,
            );
        }

        return $this->imageMetadata;
    }

    /**
     * Get image metadata in JavaScript-optimized format.
     */
    #[Computed]
    public function getImageMetadataForJs(): array
    {
        // Mock data for testing until ImageMetadataService is fully implemented
        return [
            'url' => $this->image,
            'exists' => true,
            'dimensions' => [
                'width' => 1242,
                'height' => 1996,
                'aspectRatio' => 1.333,
            ],
            'fileInfo' => [
                'sizeBytes' => 245760,
                'sizeHuman' => '240 KB',
                'mimeType' => 'image/png',
                'extension' => 'png',
            ],
            'container' => [
                'width' => 400,
                'height' => 400 / (1242 / 1996),
                'aspectRatio' => 1.333,
            ],
            'maxZoomLevel' => 3.0,
            'viewportBreakpoints' => [
                'mobile' => ['width' => 400, 'height' => 300],
                'tablet' => ['width' => 600, 'height' => 450],
                'desktop' => ['width' => 800, 'height' => 600],
            ],
            'error' => null,
            'hasError' => false,
        ];
    }

    /**
     * Get image width for JavaScript initialization.
     */
    #[Computed]
    public function getImageWidth(): int
    {
        return 800; // Mock width
    }

    /**
     * Get image height for JavaScript initialization.
     */
    #[Computed]
    public function getImageHeight(): int
    {
        return 600; // Mock height
    }

    /**
     * Check if image is valid and ready for display.
     */
    public function isImageReady(): bool
    {
        return true; // Mock - always ready for testing
    }

    /**
     * Get fallback dimensions when image is not available.
     */
    public function getFallbackDimensions(): array
    {
        return [
            'width' => min(400, $this->maxContainerWidth),
            'height' => min(400, $this->maxContainerHeight),
        ];
    }

    /**
     * Refresh image metadata (clears cache and re-extracts).
     */
    public function refreshImageMetadata(): void
    {
        $service = app(ImageMetadataService::class);
        $service->clearCache($this->image);
        $this->imageMetadata = null;

        // Force re-computation - this will be recalculated on next access
        unset($this->computedPropertyCache['imageMetadata']);
    }

    public function render()
    {
        return view('livewire.preview-image');
    }

    /**
     * Get default comments for demo purposes.
     */
    private function getDefaultComments(): array
    {
        return [
            [
                'id' => 'c91c1dbe-3ef1-4208-a8e9-9d3f010f0c21',
                'text' => 'Adjust the spacing here.',
                'x' => 12,
                'y' => 15,
                'width' => 2,
                'height' => 2,
                'type' => 'point',
                'author' => 'Alice',
                'timestamp' => '2025-06-01T10:00:00Z',
                'resolved' => false,
            ],
            [
                'id' => 'd7517139-3f2f-453e-9436-8cb31f2fc177',
                'text' => 'Consider realigning this section.',
                'x' => 35,
                'y' => 25,
                'width' => 15,
                'height' => 10,
                'type' => 'area',
                'author' => 'Bob',
                'timestamp' => '2025-06-01T10:05:00Z',
                'resolved' => false,
            ],
        ];
    }
}
