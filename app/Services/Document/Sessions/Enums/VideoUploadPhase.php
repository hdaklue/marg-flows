<?php

declare(strict_types=1);

namespace App\Services\Document\Sessions\Enums;

/**
 * Video Upload Session Phase Enum.
 *
 * Represents the specific phase/stage within a video upload session.
 * Provides more granular tracking than status.
 */
enum VideoUploadPhase: string
{
    case SINGLE_UPLOAD = 'single_upload';
    case CHUNK_UPLOAD = 'chunk_upload';
    case CHUNK_ASSEMBLY = 'chunk_assembly';
    case VIDEO_PROCESSING = 'video_processing';
    case METADATA_EXTRACTION = 'metadata_extraction';
    case THUMBNAIL_GENERATION = 'thumbnail_generation';
    case COMPLETE = 'complete';
    case ERROR = 'error';
    case CANCELLED = 'cancelled';

    /**
     * Get phases for a specific upload type.
     *
     * @return array<self>
     */
    public static function forUploadType(string $uploadType): array
    {
        return match ($uploadType) {
            'single' => [
                self::SINGLE_UPLOAD,
                self::VIDEO_PROCESSING,
                self::METADATA_EXTRACTION,
                self::THUMBNAIL_GENERATION,
                self::COMPLETE,
            ],
            'chunk' => [
                self::CHUNK_UPLOAD,
                self::CHUNK_ASSEMBLY,
                self::VIDEO_PROCESSING,
                self::METADATA_EXTRACTION,
                self::THUMBNAIL_GENERATION,
                self::COMPLETE,
            ],
            default => [],
        };
    }

    /**
     * Get all available phases.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $phase) => $phase->label(), self::cases()),
        );
    }

    /**
     * Get human-readable label for the phase.
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE_UPLOAD => 'Single Upload',
            self::CHUNK_UPLOAD => 'Chunk Upload',
            self::CHUNK_ASSEMBLY => 'Assembling Chunks',
            self::VIDEO_PROCESSING => 'Video Processing',
            self::METADATA_EXTRACTION => 'Extracting Metadata',
            self::THUMBNAIL_GENERATION => 'Generating Thumbnail',
            self::COMPLETE => 'Complete',
            self::ERROR => 'Error',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get phase description.
     */
    public function description(): string
    {
        return match ($this) {
            self::SINGLE_UPLOAD => 'Uploading file as a single unit',
            self::CHUNK_UPLOAD => 'Uploading file in chunks',
            self::CHUNK_ASSEMBLY => 'Combining uploaded chunks into final file',
            self::VIDEO_PROCESSING => 'Processing video file',
            self::METADATA_EXTRACTION => 'Extracting video metadata (duration, resolution, etc.)',
            self::THUMBNAIL_GENERATION => 'Generating video thumbnail',
            self::COMPLETE => 'All processing completed successfully',
            self::ERROR => 'An error occurred during processing',
            self::CANCELLED => 'Operation was cancelled',
        };
    }

    /**
     * Get the typical progress percentage for this phase start.
     */
    public function getProgressStart(): int
    {
        return match ($this) {
            self::SINGLE_UPLOAD, self::CHUNK_UPLOAD => 0,
            self::CHUNK_ASSEMBLY => 85,
            self::VIDEO_PROCESSING => 90,
            self::METADATA_EXTRACTION => 95,
            self::THUMBNAIL_GENERATION => 98,
            self::COMPLETE => 100,
            self::ERROR, self::CANCELLED => 0,
        };
    }

    /**
     * Get the typical progress percentage for this phase completion.
     */
    public function getProgressEnd(): int
    {
        return match ($this) {
            self::SINGLE_UPLOAD, self::CHUNK_UPLOAD => 85,
            self::CHUNK_ASSEMBLY => 90,
            self::VIDEO_PROCESSING => 95,
            self::METADATA_EXTRACTION => 98,
            self::THUMBNAIL_GENERATION, self::COMPLETE => 100,
            self::ERROR, self::CANCELLED => 0,
        };
    }

    /**
     * Check if phase indicates an upload operation.
     */
    public function isUploadPhase(): bool
    {
        return match ($this) {
            self::SINGLE_UPLOAD, self::CHUNK_UPLOAD, self::CHUNK_ASSEMBLY => true,
            default => false,
        };
    }

    /**
     * Check if phase indicates a processing operation.
     */
    public function isProcessingPhase(): bool
    {
        return match ($this) {
            self::VIDEO_PROCESSING, self::METADATA_EXTRACTION, self::THUMBNAIL_GENERATION => true,
            default => false,
        };
    }

    /**
     * Check if phase indicates a final state.
     */
    public function isFinalPhase(): bool
    {
        return match ($this) {
            self::COMPLETE, self::ERROR, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Get the corresponding status for this phase.
     */
    public function getCorrespondingStatus(): VideoUploadStatus
    {
        return match ($this) {
            self::SINGLE_UPLOAD,
            self::CHUNK_UPLOAD,
            self::CHUNK_ASSEMBLY, => VideoUploadStatus::UPLOADING,
            self::VIDEO_PROCESSING,
            self::METADATA_EXTRACTION,
            self::THUMBNAIL_GENERATION, => VideoUploadStatus::PROCESSING,
            self::COMPLETE => VideoUploadStatus::COMPLETED,
            self::ERROR => VideoUploadStatus::FAILED,
            self::CANCELLED => VideoUploadStatus::CANCELLED,
        };
    }
}
