<?php

declare(strict_types=1);

namespace App\Services\Image;

use App\DTOs\Image\ImageMetadataDTO;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Exceptions\InvalidImageDriver;
use Spatie\Image\Image;

final class ImageMetadataService
{
    private const CACHE_PREFIX = 'image_metadata:';

    private const CACHE_TTL = 3600; // 1 hour in seconds

    private const DEFAULT_MAX_WIDTH = 800;

    private const DEFAULT_MAX_HEIGHT = 600;

    /**
     * Extract comprehensive metadata from an image URL or path.
     */
    public function extractMetadata(
        string $imageUrlOrPath,
        int $maxContainerWidth = self::DEFAULT_MAX_WIDTH,
        int $maxContainerHeight = self::DEFAULT_MAX_HEIGHT,
    ): ImageMetadataDTO {
        $cacheKey = $this->getCacheKey(
            $imageUrlOrPath,
            $maxContainerWidth,
            $maxContainerHeight,
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $imageUrlOrPath,
            $maxContainerWidth,
            $maxContainerHeight,
        ) {
            return $this->doExtractMetadata(
                $imageUrlOrPath,
                $maxContainerWidth,
                $maxContainerHeight,
            );
        });
    }

    /**
     * Extract metadata without caching (useful for testing or one-time operations).
     */
    public function extractMetadataFresh(
        string $imageUrlOrPath,
        int $maxContainerWidth = self::DEFAULT_MAX_WIDTH,
        int $maxContainerHeight = self::DEFAULT_MAX_HEIGHT,
    ): ImageMetadataDTO {
        return $this->doExtractMetadata(
            $imageUrlOrPath,
            $maxContainerWidth,
            $maxContainerHeight,
        );
    }

    /**
     * Clear cached metadata for a specific image.
     */
    public function clearCache(string $imageUrlOrPath): void
    {
        $baseKey = self::CACHE_PREFIX . md5($imageUrlOrPath);

        try {
            // Clear cache keys with common suffixes
            $suffixes = [
                '',
                '_width',
                '_height',
                '_size',
                '_type',
                '_dimensions',
            ];
            foreach ($suffixes as $suffix) {
                Cache::forget($baseKey . $suffix);
            }
        } catch (Exception $e) {
            // Log warning if cache clearing fails
            Log::warning('Failed to clear image metadata cache', [
                'image' => $imageUrlOrPath,
                'error' => $e->getMessage(),
            ]);

            // Clear some common container size combinations
            $commonSizes = [
                [self::DEFAULT_MAX_WIDTH, self::DEFAULT_MAX_HEIGHT],
                [400, 300],
                [600, 400],
                [800, 600],
                [1200, 800],
            ];

            foreach ($commonSizes as [$width, $height]) {
                Cache::forget($this->getCacheKey(
                    $imageUrlOrPath,
                    $width,
                    $height,
                ));
            }
        }
    }

    /**
     * Perform the actual metadata extraction.
     */
    private function doExtractMetadata(
        string $imageUrlOrPath,
        int $maxContainerWidth,
        int $maxContainerHeight,
    ): ImageMetadataDTO {
        try {
            $imagePath = $this->resolveImagePath($imageUrlOrPath);

            if (! $imagePath || ! File::exists($imagePath)) {
                return $this->createErrorMetadata(
                    $imageUrlOrPath,
                    'Image file not found',
                    $maxContainerWidth,
                    $maxContainerHeight,
                );
            }

            if (! $this->isValidImageFile($imagePath)) {
                return $this->createErrorMetadata(
                    $imageUrlOrPath,
                    'Invalid image file format',
                    $maxContainerWidth,
                    $maxContainerHeight,
                );
            }

            $image = Image::load($imagePath);
            $fileSize = File::size($imagePath);
            $mimeType =
                File::mimeType($imagePath) ?? 'application/octet-stream';
            $extension = File::extension($imagePath) ?: 'unknown';

            $width = $image->getWidth();
            $height = $image->getHeight();
            $aspectRatio = $height > 0 ? $width / $height : 1.0;

            // Calculate optimal container dimensions
            $optimal = $this->calculateOptimalContainerDimensions(
                $width,
                $height,
                $maxContainerWidth,
                $maxContainerHeight,
            );

            // Calculate maximum zoom level based on image vs container size
            $maxZoomLevel = $this->calculateMaxZoomLevel(
                $width,
                $height,
                $optimal['width'],
                $optimal['height'],
            );

            return new ImageMetadataDTO([
                'url' => $imageUrlOrPath,
                'exists' => true,
                'width' => $width,
                'height' => $height,
                'aspectRatio' => round($aspectRatio, 4),
                'fileSizeBytes' => $fileSize,
                'fileSizeHuman' => $this->formatFileSize($fileSize),
                'mimeType' => $mimeType,
                'extension' => $extension,
                'optimalContainerWidth' => $optimal['width'],
                'optimalContainerHeight' => $optimal['height'],
                'containerAspectRatio' => round($optimal['aspectRatio'], 4),
                'maxZoomLevel' => $maxZoomLevel,
                'error' => null,
                'hasError' => false,
            ]);
        } catch (InvalidImageDriver $e) {
            Log::warning('Invalid image driver for metadata extraction', [
                'image' => $imageUrlOrPath,
                'error' => $e->getMessage(),
            ]);

            return $this->createErrorMetadata(
                $imageUrlOrPath,
                'Unsupported image format or corrupted file',
                $maxContainerWidth,
                $maxContainerHeight,
            );
        } catch (Exception $e) {
            Log::error('Failed to extract image metadata', [
                'image' => $imageUrlOrPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createErrorMetadata(
                $imageUrlOrPath,
                'Failed to process image: ' . $e->getMessage(),
                $maxContainerWidth,
                $maxContainerHeight,
            );
        }
    }

    /**
     * Resolve an image URL or path to a local file path.
     */
    private function resolveImagePath(string $imageUrlOrPath): ?string
    {
        // If it's already a local path and exists
        if (File::exists($imageUrlOrPath)) {
            return $imageUrlOrPath;
        }

        // Check if it's a public asset URL
        if (str_starts_with($imageUrlOrPath, asset(''))) {
            $relativePath = str_replace(asset(''), '', $imageUrlOrPath);
            $publicPath = public_path($relativePath);
            if (File::exists($publicPath)) {
                return $publicPath;
            }
        }

        // Check if it's a storage URL
        if (str_starts_with($imageUrlOrPath, Storage::url(''))) {
            $relativePath = str_replace(Storage::url(''), '', $imageUrlOrPath);
            if (Storage::exists($relativePath)) {
                return Storage::path($relativePath);
            }
        }

        // Try as a relative path from public directory
        $publicPath = public_path($imageUrlOrPath);
        if (File::exists($publicPath)) {
            return $publicPath;
        }

        // Try as a relative path from storage
        if (Storage::exists($imageUrlOrPath)) {
            return Storage::path($imageUrlOrPath);
        }

        return null;
    }

    /**
     * Check if the file is a valid image format.
     */
    private function isValidImageFile(string $path): bool
    {
        $mimeType = File::mimeType($path);

        $validMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp',
            'image/svg+xml',
            'image/tiff',
        ];

        return in_array($mimeType, $validMimeTypes);
    }

    /**
     * Calculate optimal container dimensions that fit within max bounds while preserving aspect ratio.
     */
    private function calculateOptimalContainerDimensions(
        int $imageWidth,
        int $imageHeight,
        int $maxWidth,
        int $maxHeight,
    ): array {
        if ($imageWidth <= 0 || $imageHeight <= 0) {
            return [
                'width' => $maxWidth,
                'height' => $maxHeight,
                'aspectRatio' => $maxWidth / $maxHeight,
            ];
        }

        $imageAspectRatio = $imageWidth / $imageHeight;
        $containerAspectRatio = $maxWidth / $maxHeight;

        if ($imageAspectRatio > $containerAspectRatio) {
            // Image is wider - fit to width
            $optimalWidth = min($imageWidth, $maxWidth);
            $optimalHeight = (int) round($optimalWidth / $imageAspectRatio);
        } else {
            // Image is taller - fit to height
            $optimalHeight = min($imageHeight, $maxHeight);
            $optimalWidth = (int) round($optimalHeight * $imageAspectRatio);
        }

        // Ensure we don't exceed maximum dimensions
        if ($optimalWidth > $maxWidth) {
            $optimalWidth = $maxWidth;
            $optimalHeight = (int) round($maxWidth / $imageAspectRatio);
        }

        if ($optimalHeight > $maxHeight) {
            $optimalHeight = $maxHeight;
            $optimalWidth = (int) round($maxHeight * $imageAspectRatio);
        }

        return [
            'width' => $optimalWidth,
            'height' => $optimalHeight,
            'aspectRatio' => $optimalHeight > 0
                ? $optimalWidth / $optimalHeight
                : 1.0,
        ];
    }

    /**
     * Calculate maximum zoom level based on image and container dimensions.
     */
    private function calculateMaxZoomLevel(
        int $imageWidth,
        int $imageHeight,
        int $containerWidth,
        int $containerHeight,
    ): float {
        if (
            $imageWidth <= 0
            || $imageHeight <= 0
            || $containerWidth <= 0
            || $containerHeight <= 0
        ) {
            return 1.0;
        }

        // Calculate zoom levels at which the image would reach the edges of a larger container
        $widthRatio = $imageWidth / $containerWidth;
        $heightRatio = $imageHeight / $containerHeight;

        // Use the larger ratio as the maximum zoom level, but cap it at a reasonable limit
        $maxZoom = max($widthRatio, $heightRatio, 1.0);

        // Cap at 5x for performance and UX reasons
        return min($maxZoom, 5.0);
    }

    /**
     * Create error metadata for failed operations.
     */
    private function createErrorMetadata(
        string $imageUrl,
        string $error,
        int $maxWidth,
        int $maxHeight,
    ): ImageMetadataDTO {
        return new ImageMetadataDTO([
            'url' => $imageUrl,
            'exists' => false,
            'width' => 0,
            'height' => 0,
            'aspectRatio' => 1.0,
            'fileSizeBytes' => 0,
            'fileSizeHuman' => '0 B',
            'mimeType' => 'application/octet-stream',
            'extension' => 'unknown',
            'optimalContainerWidth' => $maxWidth,
            'optimalContainerHeight' => $maxHeight,
            'containerAspectRatio' => $maxWidth / $maxHeight,
            'maxZoomLevel' => 1.0,
            'error' => $error,
            'hasError' => true,
        ]);
    }

    /**
     * Format file size in human readable format.
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $formattedSize = round($bytes / (1024 ** $power), 2);

        return $formattedSize . ' ' . $units[$power];
    }

    /**
     * Generate cache key for the given parameters.
     */
    private function getCacheKey(
        string $imageUrl,
        int $maxWidth,
        int $maxHeight,
    ): string {
        return
            self::CACHE_PREFIX
            . md5($imageUrl)
            . ":{$maxWidth}x{$maxHeight}";
    }
}
