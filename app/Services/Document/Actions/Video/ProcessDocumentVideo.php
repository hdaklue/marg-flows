<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use Exception;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ProcessDocumentVideo
{
    use AsAction;

    public static function getJobQueue(): string
    {
        return 'document-video-upload';
    }

    public static function getJobTries(): int
    {
        return 3;
    }

    public static function getJobTimeout(): int
    {
        return 1800; // 30 minutes
    }

    public static function getJobBackoff(): int
    {
        return 30;
    }

    /**
     * Process uploaded video file (extract metadata, generate thumbnail, etc.).
     */
    public function handle(
        string $videoPath,
        Document $document,
        ?string $sessionId = null,
        ?string $fileKey = null,
    ): array {
        // Set memory limit for video processing
        // ini_set('memory_limit', '512M');

        try {
            Log::info('ProcessDocumentVideo: Starting video processing', [
                'videoPath' => $videoPath,
                'documentId' => $document->id,
                'sessionId' => $sessionId,
                'fileKey' => $fileKey,
            ]);

            $extension = pathinfo($videoPath, PATHINFO_EXTENSION);
            $fileKey = $fileKey ?? uniqid();

            // Get video metadata from session if available, or extract from remote file as fallback
            $videoData = [];
            if ($sessionId) {
                // Try to get metadata from session first (should be set by AssembleVideoChunks)
                $sessionData = VideoUploadSessionManager::get($sessionId);
                $videoData = $sessionData['video_metadata'] ?? [];

                Log::info('ProcessDocumentVideo: Retrieved metadata from session', [
                    'sessionId' => $sessionId,
                    'hasMetadata' => ! empty($videoData),
                    'metadata' => $videoData,
                ]);
            }

            // If no metadata in session, extract from remote file as fallback
            if (empty($videoData) && config('video-upload.processing.extract_metadata', true)) {
                try {
                    Log::info('ProcessDocumentVideo: Extracting metadata from remote file as fallback', [
                        'videoPath' => $videoPath,
                    ]);

                    $videoData = ExtractVideoMetadata::run($videoPath, $sessionId);

                    // Update session with metadata if session tracking is enabled
                    if ($sessionId) {
                        VideoUploadSessionManager::updateProcessingMetadata($sessionId, 'metadata', $videoData);
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to extract video metadata from remote file', [
                        'path' => $videoPath,
                        'error' => $e->getMessage(),
                    ]);
                    $videoData = [];
                }
            }

            // Thumbnail generation removed - using modern video indicator instead

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
                'aspect_ratio' => $videoData['aspect_ratio'] ?? config(
                    'video-upload.processing.default_aspect_ratio',
                    '16:9',
                ),
                'aspect_ratio_data' => $videoData['aspect_ratio_data'] ?? null,
                'message' => 'Video uploaded and processed successfully',
            ];

            // Mark session as completed if session tracking is enabled
            if ($sessionId) {
                VideoUploadSessionManager::complete($sessionId, $result);
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

    /**
     * Handle job failure.
     */
    public function failed(
        string $videoPath,
        Document $document,
        ?string $sessionId,
        ?string $fileKey,
        Throwable $exception,
    ): void {
        Log::error('Video processing job failed permanently', [
            'path' => $videoPath,
            'document_id' => $document->id,
            'sessionId' => $sessionId,
            'fileKey' => $fileKey,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark session as failed if session tracking is enabled
        if ($sessionId) {
            VideoUploadSessionManager::fail($sessionId, $exception->getMessage());
        }
    }
}
