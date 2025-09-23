<?php

declare(strict_types=1);

namespace App\Services\Document\Sessions\Enums;

/**
 * Video Upload Type Enum.
 *
 * Represents the type of video upload method used.
 */
enum VideoUploadType: string
{
    case SINGLE = 'single';
    case CHUNK = 'chunk';

    /**
     * Get all available upload types.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $type) => $type->label(), self::cases()),
        );
    }

    /**
     * Get human-readable label for the upload type.
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single Upload',
            self::CHUNK => 'Chunked Upload',
        };
    }

    /**
     * Get description for the upload type.
     */
    public function description(): string
    {
        return match ($this) {
            self::SINGLE => 'Upload entire file as a single unit',
            self::CHUNK => 'Upload file in multiple chunks for better reliability',
        };
    }

    /**
     * Get the initial phase for this upload type.
     */
    public function getInitialPhase(): VideoUploadPhase
    {
        return match ($this) {
            self::SINGLE => VideoUploadPhase::SINGLE_UPLOAD,
            self::CHUNK => VideoUploadPhase::CHUNK_UPLOAD,
        };
    }

    /**
     * Check if this upload type requires chunking.
     */
    public function requiresChunking(): bool
    {
        return $this === self::CHUNK;
    }
}
