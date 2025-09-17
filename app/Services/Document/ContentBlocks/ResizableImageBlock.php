<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use Faker\Generator;

final class ResizableImageBlock extends Block
{
    /**
     * Generate fake data for testing.
     */
    public static function fake(Generator $faker): array
    {
        $imageCount = $faker->numberBetween(1, 4);
        $files = [];

        for ($i = 0; $i < $imageCount; $i++) {
            $files[] = [
                'filename' => $faker->uuid() . '.jpg',
                'caption' => $faker->optional(0.7)->sentence(),
                'width' => $faker->numberBetween(800, 1920),
                'height' => $faker->numberBetween(600, 1080),
            ];
        }

        return [
            'files' => $files,
            'caption' => $faker->optional(0.5)->sentence(),
        ];
    }

    /**
     * Validation rules for the resizable image block data.
     */
    public function rules(): array
    {
        return [
            'files' => ['array'],
            'files.*.filename' => ['required', 'string'],
            'files.*.caption' => ['nullable', 'string'],
            'files.*.width' => ['nullable', 'integer', 'min:1'],
            'files.*.height' => ['nullable', 'integer', 'min:1'],
            'caption' => ['nullable', 'string'],
        ];
    }

    /**
     * Allowed HTML tags for content purification.
     */
    public function allows(): array
    {
        return [
            'caption' => '*', // Allow all HTML in captions for rich text
        ];
    }

    /**
     * Check if the block has any images.
     */
    public function hasImages(): bool
    {
        $files = $this->get('files', []);

        return is_array($files) && count($files) > 0;
    }

    /**
     * Get the number of images in the block.
     */
    public function getImageCount(): int
    {
        $files = $this->get('files', []);

        return is_array($files) ? count($files) : 0;
    }

    /**
     * Get all image filenames.
     */
    public function getFilenames(): array
    {
        $files = $this->get('files', []);
        if (! is_array($files)) {
            return [];
        }

        return array_map(fn ($file) => $file['filename'] ?? '', $files);
    }

    /**
     * Get the gallery caption.
     */
    public function getCaption(): ?string
    {
        return $this->get('caption');
    }

    /**
     * Check if the block is empty (no images).
     */
    public function isEmpty(): bool
    {
        return ! $this->hasImages();
    }

    /**
     * Render the resizable image block to HTML.
     * Note: This is a basic implementation. URL resolution should be handled
     * at the application level when rendering the complete document.
     */
    public function render(): string
    {
        if (! $this->hasImages()) {
            return '';
        }

        $files = $this->get('files', []);
        $caption = $this->getCaption();

        // Start building the HTML with data attributes for external processing
        $html = '<div class="resizable-image-gallery" data-block-type="resizableImage">';

        // Single image or gallery
        if (count($files) === 1) {
            $file = $files[0];
            $html .= $this->renderSingleImage($file);
        } else {
            $html .= $this->renderImageGallery($files);
        }

        // Add gallery caption if present
        if ($caption) {
            $html .= '<div class="resizable-image-caption">' . htmlspecialchars($caption) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single image with filename as data attribute.
     */
    private function renderSingleImage(array $file): string
    {
        $filename = $file['filename'] ?? '';
        $caption = $file['caption'] ?? '';
        $width = $file['width'] ?? null;
        $height = $file['height'] ?? null;

        $html = '<div class="resizable-image-single">';
        $html .= '<img data-filename="' . htmlspecialchars($filename) . '"';
        $html .= ' alt="' . htmlspecialchars($caption) . '"';

        if ($width && $height) {
            $html .= ' width="' . $width . '" height="' . $height . '"';
        }

        $html .= ' class="resizable-image-img">';

        if ($caption) {
            $html .= '<div class="resizable-image-single-caption">' . htmlspecialchars($caption) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render multiple images as a gallery with filenames as data attributes.
     */
    private function renderImageGallery(array $files): string
    {
        $html = '<div class="resizable-image-grid">';

        foreach ($files as $file) {
            $filename = $file['filename'] ?? '';
            $caption = $file['caption'] ?? '';

            $html .= '<div class="resizable-image-item">';
            $html .= '<img data-filename="' . htmlspecialchars($filename) . '"';
            $html .= ' alt="' . htmlspecialchars($caption) . '"';
            $html .= ' class="resizable-image-thumb">';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
