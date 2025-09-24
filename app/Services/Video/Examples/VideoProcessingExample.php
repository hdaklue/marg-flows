<?php

declare(strict_types=1);

namespace App\Services\Video\Examples;

use App\Services\Video\Conversions\Conversion480p;
use App\Services\Video\Conversions\Conversion720p;
use App\Services\Video\Facades\Video;
use App\Services\Video\ValueObjects\Dimension;

/**
 * Example usage of the Video Service with Facade.
 *
 * This class demonstrates various ways to use the video service
 * for common video processing tasks.
 */
final class VideoProcessingExample
{
    /**
     * Example 1: Process a video from storage.
     */
    public static function processFromStorage(): void
    {
        // Load video from storage and apply 720p conversion
        Video::fromDisk('videos/uploads/input.mp4')
            ->trim(10, 60) // Extract 1 minute starting at 10s
            ->resize(new Dimension(1280, 720))
            ->convert(new Conversion720p())
            ->save('videos/processed/output-720p.mp4');
    }

    /**
     * Example 2: Process a video from URL.
     */
    public static function processFromUrl(): void
    {
        // Load video from URL and process
        Video::fromUrl('https://example.com/source-video.mp4')
            ->trim(0, 30) // First 30 seconds
            ->convert(new Conversion480p())
            ->save('videos/processed/url-video-480p.mp4');
    }

    /**
     * Example 3: Create multiple quality versions.
     */
    public static function createMultipleQualities(): void
    {
        $sourceVideo = 'videos/uploads/master-video.mp4';

        // Create 720p version
        Video::fromDisk($sourceVideo)
            ->trim(5, 120) // 2 minutes starting at 5s
            ->convert(new Conversion720p())
            ->save('videos/processed/video-720p.mp4');

        // Create 480p version
        Video::fromDisk($sourceVideo)
            ->trim(5, 120) // Same trimming
            ->convert(new Conversion480p())
            ->save('videos/processed/video-480p.mp4');
    }

    /**
     * Example 4: Complex video manipulation.
     */
    public static function complexProcessing(): void
    {
        $watermarkPath = storage_path('app/watermarks/logo.png');

        Video::fromDisk('videos/uploads/raw-footage.mp4')
            ->trim(30, 180) // 3 minutes starting at 30s
            ->resize(new Dimension(1920, 1080)) // Full HD
            ->crop(100, 50, new Dimension(1720, 980)) // Crop with margins
            ->watermark($watermarkPath, 'bottom-right', 0.8) // Add watermark
            ->setFrameRate(30) // 30 FPS
            ->convert(new Conversion720p())
            ->save('videos/processed/final-edited.mp4');
    }

    /**
     * Example 5: Batch processing multiple files.
     */
    public static function batchProcess(array $videoFiles): void
    {
        foreach ($videoFiles as $index => $videoFile) {
            Video::fromDisk($videoFile)
                ->trim(0, 45) // First 45 seconds
                ->resize(new Dimension(854, 480)) // Standard size
                ->convert(new Conversion480p())
                ->save("videos/processed/batch-{$index}.mp4");
        }
    }

    /**
     * Example 6: Using saveAs with specific conversion.
     */
    public static function saveAsExample(): void
    {
        $conversion = new Conversion720p();

        Video::fromUrl('https://example.com/source.mp4')
            ->trim(15, 90) // 90 seconds starting at 15s
            ->resize(new Dimension(1280, 720))
            ->saveAs('videos/processed/converted.mp4', $conversion);
    }

    /**
     * Example 7: Social media optimized versions.
     */
    public static function createSocialMediaVersions(): void
    {
        $sourceVideo = 'videos/uploads/content.mp4';

        // Instagram square format
        Video::fromDisk($sourceVideo)
            ->trim(0, 60) // 1 minute max for Instagram
            ->crop(0, 0, new Dimension(1080, 1080)) // Square crop
            ->convert(new Conversion720p())
            ->save('videos/processed/instagram-square.mp4');

        // TikTok vertical format
        Video::fromDisk($sourceVideo)
            ->trim(0, 60) // 1 minute max
            ->resize(new Dimension(720, 1280)) // Vertical 9:16
            ->convert(new Conversion720p())
            ->save('videos/processed/tiktok-vertical.mp4');

        // YouTube optimized
        Video::fromDisk($sourceVideo)
            ->convert(new Conversion720p())
            ->save('videos/processed/youtube-720p.mp4');
    }
}
