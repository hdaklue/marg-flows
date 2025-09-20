<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Services\Video\VideoManager;
use Exception;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class ExtractVideoMetadata
{
    use AsAction;

    /**
     * Extract video metadata using VideoManager.
     */
    public function handle(string $path, ?string $sessionId = null): array
    {
        try {
            // Determine disk based on path - use local chunks disk for local files, do_spaces for remote files
            $disk = $this->determineDisk($path);
            
            Log::info('Extracting video metadata using VideoManager', [
                'path' => $path,
                'disk' => $disk,
                'sessionId' => $sessionId,
            ]);
            
            // Use VideoManager to create a Video object and extract metadata
            $videoManager = app(VideoManager::class);
            $video = $videoManager->fromDisk($path, $disk);
            
            // Get all metadata from Video object
            $metadata = $video->getMetadata();
            
            Log::info('Video metadata extracted successfully', [
                'path' => $path,
                'width' => $metadata['dimension']['width'],
                'height' => $metadata['dimension']['height'],
                'duration' => $metadata['duration'],
                'fileSize' => $metadata['fileSize']['bytes'],
            ]);

            return [
                'width' => $metadata['dimension']['width'],
                'height' => $metadata['dimension']['height'],
                'duration' => $metadata['duration'],
                'size' => $metadata['fileSize']['bytes'],
                'format' => $metadata['extension'],
                'aspect_ratio' => $metadata['dimension']['aspect_ratio']['ratio'] ?? '16:9',
                'aspect_ratio_data' => $metadata['dimension']['aspect_ratio'] ?? null,
            ];
        } catch (Exception $e) {
            Log::warning('Failed to extract video metadata', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            // Try to get file size using the determined disk
            $disk = $this->determineDisk($path);
            try {
                $fileSize = Storage::disk($disk)->size($path);
            } catch (Exception $sizeException) {
                Log::warning('Failed to get file size for video metadata fallback', [
                    'path' => $path,
                    'disk' => $disk,
                    'error' => $sizeException->getMessage(),
                ]);
                $fileSize = null;
            }

            return [
                'width' => null,
                'height' => null,
                'duration' => null,
                'size' => $fileSize,
                'aspect_ratio' => '16:9',
                'aspect_ratio_data' => null,
            ];
        }
    }

    /**
     * Determine which disk to use based on the file path.
     */
    private function determineDisk(string $path): string
    {
        // If path starts with tenant directory structure, it's likely on do_spaces
        // Otherwise, assume it's on local chunks disk
        if (preg_match('/^[a-f0-9]{32}\/documents\//', $path)) {
            return config('directory-chunks.tenant_isolation.disk', 'do_spaces');
        }
        
        return config('chunked-upload.storage.disk', 'local_chunks');
    }
}
