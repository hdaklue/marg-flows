<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use Exception;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class ProcessDocumentVideo
{
    use AsAction;

    /**
     * Process uploaded video file (extract metadata, generate thumbnail, etc.).
     */
    public function handle(string $videoPath, Document $document, ?string $fileKey = null): array
    {
        try {
            $extension = pathinfo($videoPath, PATHINFO_EXTENSION);
            $fileKey = $fileKey ?? uniqid();

            // Extract video metadata if enabled
            $videoData = [];
            if (config('video-upload.processing.extract_metadata', true)) {
                try {
                    $videoData = ExtractVideoMetadata::run($videoPath);
                } catch (Exception $e) {
                    Log::warning('Failed to extract video metadata', [
                        'path' => $videoPath,
                        'error' => $e->getMessage(),
                    ]);
                    $videoData = [];
                }
            }

            // Generate thumbnail if enabled
            $thumbnailPath = null;
            if (config('video-upload.processing.generate_thumbnails', true)) {
                $duration = $videoData['duration'] ?? null;
                if ($duration && $duration > 0) {
                    try {
                        $thumbnailPath = GenerateVideoThumbnail::run($videoPath, $duration, $document);
                    } catch (Exception $e) {
                        Log::warning('Failed to generate video thumbnail', [
                            'path' => $videoPath,
                            'error' => $e->getMessage(),
                        ]);
                        $thumbnailPath = null;
                    }
                }
            }

            $filename = basename($videoPath);

            $result = [
                'success' => true,
                'completed' => true,
                'fileKey' => $fileKey,
                'filename' => $filename,
                'width' => $videoData['width'] ?? null,
                'height' => $videoData['height'] ?? null,
                'duration' => $videoData['duration'] ?? null,
                'size' => $videoData['size'] ?? null,
                'format' => strtolower($extension),
                'original_format' => $extension,
                'aspect_ratio' => $videoData['aspect_ratio'] ?? config('video-upload.processing.default_aspect_ratio', '16:9'),
                'aspect_ratio_data' => $videoData['aspect_ratio_data'] ?? null,
                'message' => 'Video uploaded and processed successfully',
            ];

            if ($thumbnailPath) {
                $result['thumbnail'] = basename($thumbnailPath);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to process video file', [
                'path' => $videoPath,
                'fileKey' => $fileKey,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
