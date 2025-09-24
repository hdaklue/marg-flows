<?php

declare(strict_types=1);

namespace App\Services\Document\ContentBlocks;

use BumpCore\EditorPhp\Block\Block;
use Exception;
use Faker\Generator;

final class VideoUploadBlock extends Block
{
    /**
     * Get predefined video formats.
     */
    public static function getPredefinedFormats(): array
    {
        return ['mp4', 'webm', 'ogg'];
    }

    /**
     * Generate fake data for testing.
     */
    public static function fake(Generator $faker): array
    {
        $formats = self::getPredefinedFormats();

        return [
            'file' => [
                'filename' => $faker->uuid() . '.' . $faker->randomElement($formats),
                'width' => $faker->numberBetween(640, 1920),
                'height' => $faker->numberBetween(480, 1080),
                'duration' => $faker->numberBetween(30, 600), // 30 seconds to 10 minutes
                'size' => $faker->numberBetween(1024 * 1024, 100 * 1024 * 1024), // 1MB to 100MB
                'format' => $faker->randomElement($formats),
                'aspect_ratio' => $faker->randomElement(['16:9', '4:3', '21:9']),
            ],
            'caption' => $faker->optional(0.6)->sentence(),
        ];
    }

    /**
     * Validation rules for the video upload block data.
     */
    public function rules(): array
    {
        return [
            'file' => ['nullable', 'array'],
            'file.filename' => ['required_with:file', 'string'],
            'file.width' => ['nullable', 'integer', 'min:1'],
            'file.height' => ['nullable', 'integer', 'min:1'],
            'file.duration' => ['nullable', 'numeric', 'min:0'],
            'file.size' => ['nullable', 'integer', 'min:0'],
            'file.format' => ['nullable', 'string', 'in:mp4,webm,ogg'],
            'file.aspect_ratio' => ['nullable', 'string'],
            'file.aspect_ratio_data' => ['nullable', 'array'],
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Allowed HTML tags for content purification.
     */
    public function allows(): array
    {
        return [
            'caption' => 'b,i,em,strong,a', // Allow basic formatting and links in captions
        ];
    }

    /**
     * Check if the block has a valid video.
     */
    public function hasVideo(): bool
    {
        $file = $this->get('file');

        return (
            is_array($file)
            && !empty($file['filename'])
            && is_string($file['filename'])
            && trim($file['filename']) !== ''
        );
    }

    /**
     * Get the video filename.
     */
    public function getFilename(): null|string
    {
        $file = $this->get('file');

        return is_array($file) ? $file['filename'] ?? null : null;
    }

    /**
     * Get the video caption.
     */
    public function getCaption(): null|string
    {
        return $this->get('caption');
    }

    /**
     * Get video width.
     */
    public function getWidth(): null|int
    {
        $file = $this->get('file');
        $width = is_array($file) ? $file['width'] ?? null : null;

        return is_numeric($width) ? (int) $width : null;
    }

    /**
     * Get video height.
     */
    public function getHeight(): null|int
    {
        $file = $this->get('file');
        $height = is_array($file) ? $file['height'] ?? null : null;

        return is_numeric($height) ? (int) $height : null;
    }

    /**
     * Get video duration in seconds.
     */
    public function getDuration(): null|float
    {
        $file = $this->get('file');
        $duration = is_array($file) ? $file['duration'] ?? null : null;

        return is_numeric($duration) ? (float) $duration : null;
    }

    /**
     * Get video file size in bytes.
     */
    public function getSize(): null|int
    {
        $file = $this->get('file');
        $size = is_array($file) ? $file['size'] ?? null : null;

        return is_numeric($size) ? (int) $size : null;
    }

    /**
     * Get video format.
     */
    public function getFormat(): null|string
    {
        $file = $this->get('file');

        return is_array($file) ? $file['format'] ?? null : null;
    }

    /**
     * Get video aspect ratio.
     */
    public function getAspectRatio(): string
    {
        $file = $this->get('file');

        return is_array($file) ? $file['aspect_ratio'] ?? '16:9' : '16:9';
    }

    /**
     * Get formatted duration (e.g., "2:35" for 2 minutes 35 seconds).
     */
    public function getFormattedDuration(): string
    {
        $duration = $this->getDuration();

        if ($duration === null) {
            return '';
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get formatted file size (e.g., "15.3 MB").
     */
    public function getFormattedSize(): string
    {
        $size = $this->getSize();

        if ($size === null) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $bytes = $size;

        while ($bytes >= 1024 && $i < (count($units) - 1)) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    /**
     * Get video dimensions as string (e.g., "1920×1080").
     */
    public function getDimensions(): string
    {
        $width = $this->getWidth();
        $height = $this->getHeight();

        if ($width === null || $height === null) {
            return '';
        }

        return $width . '×' . $height;
    }

    /**
     * Check if the video format is supported.
     */
    public function isSupportedFormat(): bool
    {
        $format = $this->getFormat();

        return $format !== null && in_array(strtolower($format), self::getPredefinedFormats());
    }

    /**
     * Check if the block is empty (no video).
     */
    public function isEmpty(): bool
    {
        return !$this->hasVideo();
    }

    /**
     * Get video metadata as array.
     */
    public function getMetadata(): array
    {
        if (!$this->hasVideo()) {
            return [];
        }

        $metadata = [];

        if ($duration = $this->getFormattedDuration()) {
            $metadata[] = $duration;
        }

        if ($dimensions = $this->getDimensions()) {
            $metadata[] = $dimensions;
        }

        if ($size = $this->getFormattedSize()) {
            $metadata[] = $size;
        }

        if ($format = $this->getFormat()) {
            $metadata[] = strtoupper($format);
        }

        return $metadata;
    }

    /**
     * Get video metadata as formatted string.
     */
    public function getMetadataString(): string
    {
        $metadata = $this->getMetadata();

        return implode(' • ', $metadata);
    }

    /**
     * Validate video file constraints.
     */
    public function validateVideoConstraints(array $constraints = []): array
    {
        $errors = [];

        if (!$this->hasVideo()) {
            return $errors;
        }

        // Validate file size
        if (!empty($constraints['max_size'])) {
            $size = $this->getSize();
            if ($size && $size > $constraints['max_size']) {
                $maxSizeMB = round($constraints['max_size'] / (1024 * 1024), 1);
                $currentSizeMB = round($size / (1024 * 1024), 1);
                $errors[] = "Video file size ({$currentSizeMB} MB) exceeds maximum allowed size ({$maxSizeMB} MB)";
            }
        }

        // Validate duration
        if (!empty($constraints['max_duration'])) {
            $duration = $this->getDuration();
            if ($duration && $duration > $constraints['max_duration']) {
                $maxDuration = $this->formatDurationFromSeconds($constraints['max_duration']);
                $currentDuration = $this->getFormattedDuration();
                $errors[] = "Video duration ({$currentDuration}) exceeds maximum allowed duration ({$maxDuration})";
            }
        }

        // Validate format
        if (!$this->isSupportedFormat()) {
            $supportedFormats = implode(', ', array_map(
                'strtoupper',
                self::getPredefinedFormats(),
            ));
            $currentFormat = strtoupper($this->getFormat() ?? 'unknown');
            $errors[] = "Video format ({$currentFormat}) is not supported. Supported formats: {$supportedFormats}";
        }

        return $errors;
    }

    /**
     * Render the video upload block to HTML.
     */
    public function render(): string
    {
        throw new Exception('Create a separate view for this block');
    }

    /**
     * Render the video upload block to HTML with RTL support.
     */
    public function renderRtl(): string
    {
        throw new Exception('Create a separate view for this block');
    }

    /**
     * Get summary data for analytics or reporting.
     */
    public function getSummary(): array
    {
        return [
            'type' => 'video',
            'filename' => $this->getFilename(),
            'caption' => $this->getCaption(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'duration' => $this->getDuration(),
            'size' => $this->getSize(),
            'format' => $this->getFormat(),
            'aspect_ratio' => $this->getAspectRatio(),
            'dimensions' => $this->getDimensions(),
            'formatted_duration' => $this->getFormattedDuration(),
            'formatted_size' => $this->getFormattedSize(),
            'metadata_string' => $this->getMetadataString(),
            'is_supported_format' => $this->isSupportedFormat(),
            'is_empty' => $this->isEmpty(),
        ];
    }

    /**
     * Helper to format duration from seconds.
     */
    private function formatDurationFromSeconds(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}
