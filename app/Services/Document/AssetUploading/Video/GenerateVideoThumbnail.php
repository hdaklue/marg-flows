<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

final class GenerateVideoThumbnail
{
    use AsAction;

    /**
     * Generate video thumbnail using Laravel FFmpeg.
     */
    public function handle(
        string $videoPath,
        float $duration,
        Document $document,
        ?string $sessionId = null,
    ): ?string {
        try {
            $extractionTime = $duration < 10 ? $duration * 0.1 : 1.0;
            $videoFilename = pathinfo($videoPath, PATHINFO_FILENAME);
            $thumbnailFilename = $videoFilename . '_thumb.jpg';

            $directoryManager = DocumentDirectoryManager::make($document);
            $thumbnailStrategy = $directoryManager->videos()->asThumbnails();
            $thumbnailPath = $thumbnailStrategy->getDirectory() . '/' . $thumbnailFilename;

            // Read video from local chunks disk, save thumbnail to document storage disk
            $videoDisk = config('chunked-upload.storage.disk', 'local_chunks');
            $thumbnailDisk = config('document.storage.disk', 'do_spaces');

            Log::info('Generating video thumbnail', [
                'videoPath' => $videoPath,
                'thumbnailPath' => $thumbnailPath,
                'videoDisk' => $videoDisk,
                'thumbnailDisk' => $thumbnailDisk,
                'extractionTime' => $extractionTime,
            ]);

            Storage::disk($thumbnailDisk)->makeDirectory($thumbnailStrategy->getDirectory());

            $media = FFMpeg::fromDisk($videoDisk)->open($videoPath);
            $frame = $media->getFrameFromSeconds($extractionTime);
            $frame->export()->toDisk($thumbnailDisk)->save($thumbnailPath);

            Log::info('Video thumbnail generated successfully', [
                'video_path' => $videoPath,
                'thumbnail_path' => $thumbnailPath,
                'extraction_time' => $extractionTime,
            ]);

            $this->warmThumbnailCache($thumbnailPath, $thumbnailDisk);

            return $thumbnailPath;
        } catch (Exception $e) {
            Log::warning('Failed to generate video thumbnail', [
                'video_path' => $videoPath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Warm the cache with newly generated thumbnail.
     */
    private function warmThumbnailCache(string $thumbnailPath, string $disk): void
    {
        try {
            $content = Storage::disk($disk)->get($thumbnailPath);
            $lastModified = Storage::disk($disk)->lastModified($thumbnailPath);
            $cacheKey = 'file_content:' . md5($thumbnailPath . $lastModified);
            Cache::put($cacheKey, $content, 3600);

            Log::info('Thumbnail cached successfully', [
                'thumbnail_path' => $thumbnailPath,
                'cache_key' => $cacheKey,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to cache thumbnail', [
                'thumbnail_path' => $thumbnailPath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
