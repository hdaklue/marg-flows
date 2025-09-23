<?php

declare(strict_types=1);

namespace App\Services\Document\Sessions\Enums;

/**
 * Video Upload Session Status Enum.
 *
 * Represents the high-level status of a video upload session.
 */
enum VideoUploadStatus: string
{
    case UPLOADING = 'uploading';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get all available statuses.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $status) => $status->label(), self::cases()),
        );
    }

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::UPLOADING => 'Uploading',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get status description.
     */
    public function description(): string
    {
        return match ($this) {
            self::UPLOADING => 'File is being uploaded to the server',
            self::PROCESSING => 'File is being processed (metadata extraction, thumbnail generation)',
            self::COMPLETED => 'Upload and processing completed successfully',
            self::FAILED => 'Upload or processing failed with an error',
            self::CANCELLED => 'Upload was cancelled by the user',
        };
    }

    /**
     * Check if status indicates an active operation.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::UPLOADING, self::PROCESSING => true,
            self::COMPLETED, self::FAILED, self::CANCELLED => false,
        };
    }

    /**
     * Check if status indicates a final state.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::CANCELLED => true,
            self::UPLOADING, self::PROCESSING => false,
        };
    }

    /**
     * Check if status indicates success.
     */
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if status indicates failure.
     */
    public function isFailure(): bool
    {
        return match ($this) {
            self::FAILED, self::CANCELLED => true,
            default => false,
        };
    }
}
