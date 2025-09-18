<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Services\Upload\DTOs\ChunkConfig;
use App\Support\FileSize;

final class ChunkConfigManager
{
    /**
     * Simple plan configuration - conservative settings for basic users.
     */
    public static function simple(): ChunkConfig
    {
        return new ChunkConfig(
            maxFileSize: FileSize::fromMB(50), // 50MB max file
            chunkSize: FileSize::fromMB(1), // 1MB chunks
            useChunkedUpload: true,
            maxConcurrentUploads: 2, // Conservative concurrency
            retryAttempts: 2, // Fewer retries
            timeoutSeconds: 180, // 3 minutes timeout
        );
    }

    /**
     * Advanced plan configuration - balanced settings for power users.
     */
    public static function advanced(): ChunkConfig
    {
        return new ChunkConfig(
            maxFileSize: FileSize::fromMB(250), // 250MB max file
            chunkSize: FileSize::fromMB(5), // 5MB chunks
            useChunkedUpload: true,
            maxConcurrentUploads: 3, // Balanced concurrency
            retryAttempts: 3, // Standard retries
            timeoutSeconds: 300, // 5 minutes timeout
        );
    }

    /**
     * Ultimate plan configuration - high-performance settings for enterprise users.
     */
    public static function ultimate(): ChunkConfig
    {
        return new ChunkConfig(
            maxFileSize: FileSize::fromGB(2), // 2GB max file
            chunkSize: FileSize::fromMB(10), // 10MB chunks
            useChunkedUpload: true,
            maxConcurrentUploads: 5, // High concurrency
            retryAttempts: 5, // More retries for large files
            timeoutSeconds: 600, // 10 minutes timeout
        );
    }

    /**
     * Get configuration for a specific plan.
     */
    public static function forPlan(string $plan): ChunkConfig
    {
        return match ($plan) {
            'simple' => self::simple(),
            'advanced' => self::advanced(),
            'ultimate' => self::ultimate(),
            default => self::simple(), // Fallback to simple
        };
    }

    /**
     * Get configuration optimized for images (smaller chunks, faster processing).
     */
    public static function forImages(string $plan = 'simple'): ChunkConfig
    {
        $baseConfig = self::forPlan($plan);

        return new ChunkConfig(
            maxFileSize: match ($plan) {
                'simple' => FileSize::fromMB(5),
                'advanced' => FileSize::fromMB(15),
                'ultimate' => FileSize::fromMB(25),
                default => FileSize::fromMB(5),
            },
            chunkSize: FileSize::fromMB(1), // Always use small chunks for images
            useChunkedUpload: false, // Images typically don't need chunking
            maxConcurrentUploads: $baseConfig->maxConcurrentUploads,
            retryAttempts: $baseConfig->retryAttempts,
            timeoutSeconds: 120, // Shorter timeout for images
        );
    }

    /**
     * Get configuration optimized for videos (larger chunks, longer timeouts).
     */
    public static function forVideos(string $plan = 'simple'): ChunkConfig
    {
        $baseConfig = self::forPlan($plan);

        return new ChunkConfig(
            maxFileSize: match ($plan) {
                'simple' => FileSize::fromMB(50),
                'advanced' => FileSize::fromMB(100),
                'ultimate' => FileSize::fromMB(200),
                default => FileSize::fromMB(100),
            },
            chunkSize: $baseConfig->chunkSize,
            useChunkedUpload: true, // Videos always use chunking
            maxConcurrentUploads: $baseConfig->maxConcurrentUploads,
            retryAttempts: $baseConfig->retryAttempts + 1, // Extra retry for large videos
            timeoutSeconds: $baseConfig->timeoutSeconds * 2, // Longer timeout for videos
        );
    }
}
