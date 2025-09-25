<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video\Upload;

use App\Models\Document;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use App\Services\Video\Resolutions\Resolution480p;
use App\Services\Video\Services\ResolutionExporter;
use App\Services\Video\VideoManager;
use Exception;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class ConvertVideoAction
{
    use AsAction;

    public int $tries = 3;

    public int $timeout = 20 * 60; // 20 minutes for conversion

    public static function getJobQueue(): string
    {
        return 'document-video-upload';
    }

    /**
     * Convert video to 480p and extract metadata.
     */
    public function handle(
        string $localFinalPath,
        Document $document,
        ?string $videoSessionId = null,
    ): array {
        try {
            Log::info('Starting video conversion', [
                'localPath' => $localFinalPath,
                'videoSessionId' => $videoSessionId,
                'documentId' => $document->id,
            ]);

            // Wait a moment to ensure assembled file is fully written
            sleep(3);

            $videoMetadata = null;
            $chunksDisk = config('chunked-upload.storage.disk');

            // Check if FFmpeg is available before attempting conversion
            $ffmpegAvailable = $this->checkFFmpegAvailability();

            if ($ffmpegAvailable) {
                Log::info('Video conversion setup', [
                    'localFinalPath' => $localFinalPath,
                    'chunksDisk' => $chunksDisk,
                    'willOverwriteOriginal' => true,
                ]);

                // Use ResolutionExporter directly to specify exact output path
                $exporter = ResolutionExporter::start($localFinalPath, $chunksDisk);

                // Create video object to get orientation for 480p conversion
                $videoManager = app(VideoManager::class);
                $video = $videoManager->fromDisk($localFinalPath, $chunksDisk);
                $orientation = $video->getOrientation();

                // Create 480p conversion and export to temporary file with _mod suffix
                $pathInfo = pathinfo($localFinalPath);
                $tempConvertedPath =
                    $pathInfo['dirname']
                    . '/'
                    . $pathInfo['filename']
                    . '_mod.'
                    . $pathInfo['extension'];

                $conversion = new Resolution480p($orientation);
                $conversionResult = $exporter->export($conversion, $tempConvertedPath);

                // If conversion successful, replace original file with converted one
                if ($conversionResult->isSuccessful()) {
                    Log::info('Replacing original with converted file', [
                        'originalPath' => $localFinalPath,
                        'convertedPath' => $tempConvertedPath,
                    ]);

                    // Delete original file and rename converted file to original name
                    Storage::disk($chunksDisk)->delete($localFinalPath);
                    Storage::disk($chunksDisk)->move($tempConvertedPath, $localFinalPath);

                    Log::info('File replacement completed', [
                        'finalPath' => $localFinalPath,
                    ]);
                } else {
                    Log::warning('Conversion failed, keeping original file', [
                        'originalPath' => $localFinalPath,
                        'conversionError' => $conversionResult->getErrorMessage(),
                    ]);

                    // Clean up failed conversion file if it exists
                    if (Storage::disk($chunksDisk)->exists($tempConvertedPath)) {
                        Storage::disk($chunksDisk)->delete($tempConvertedPath);
                    }
                }

                Log::info('Video conversion completed', [
                    'localPath' => $localFinalPath,
                    'successful' => $conversionResult->isSuccessful(),
                ]);
            } else {
                Log::warning('FFmpeg not available, skipping video conversion', [
                    'localPath' => $localFinalPath,
                    'message' => 'Video will be uploaded without compression',
                ]);
            }

            // Extract metadata from the (possibly converted) local file
            $videoMetadata = $this->extractMetadataFromLocalFileWithFallback(
                $localFinalPath,
                $videoSessionId,
            );

            // Update session with metadata
            if ($videoSessionId) {
                VideoUploadSessionManager::updateProcessingMetadata(
                    $videoSessionId,
                    'metadata',
                    $videoMetadata,
                );

                VideoUploadSessionManager::update($videoSessionId, [
                    'phase' => 'upload',
                    'status' => 'processing',
                ]);
            }

            Log::info('Video conversion phase completed', [
                'localPath' => $localFinalPath,
                'documentId' => $document->id,
            ]);

            // Chain to storage push action with a small delay
            PushToStorageAction::dispatch(
                $localFinalPath,
                $document,
                $videoSessionId,
                $videoMetadata,
            )
                ->delay(now()->addSeconds(2))
                ->onQueue('document-video-upload');

            return $videoMetadata;
        } catch (Exception $e) {
            Log::error('Video conversion failed', [
                'localPath' => $localFinalPath,
                'videoSessionId' => $videoSessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'documentId' => $document->id,
            ]);

            // Mark session as failed
            if ($videoSessionId) {
                VideoUploadSessionManager::fail(
                    $videoSessionId,
                    'Failed to convert video: ' . $e->getMessage(),
                );
            }

            throw $e;
        }
    }

    /**
     * Check if FFmpeg is available on the system.
     */
    private function checkFFmpegAvailability(): bool
    {
        try {
            // Try to execute ffmpeg -version command
            $output = shell_exec('ffmpeg -version 2>&1');

            return $output !== null && str_contains($output, 'ffmpeg version');
        } catch (Exception $e) {
            Log::warning('FFmpeg availability check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Extract metadata from local file with fallback for when FFmpeg is unavailable.
     */
    private function extractMetadataFromLocalFileWithFallback(
        string $localPath,
        ?string $sessionId = null,
    ): array {
        try {
            return $this->extractMetadataFromLocalFile($localPath, $sessionId);
        } catch (Exception $e) {
            Log::warning('Failed to extract video metadata, using file size fallback', [
                'localPath' => $localPath,
                'error' => $e->getMessage(),
            ]);

            // Fallback: just get file size and return basic metadata
            $chunksDisk = config('chunked-upload.storage.disk', 'local_chunks');
            try {
                $fileSize = Storage::disk($chunksDisk)->size($localPath);
            } catch (Exception $sizeException) {
                $fileSize = null;
            }

            return [
                'width' => null,
                'height' => null,
                'duration' => null,
                'size' => $fileSize,
                'format' => pathinfo($localPath, PATHINFO_EXTENSION),
                'aspect_ratio' => '16:9',
                'aspect_ratio_data' => null,
            ];
        }
    }

    /**
     * Extract metadata from local chunks disk specifically.
     */
    private function extractMetadataFromLocalFile(
        string $localPath,
        ?string $sessionId = null,
    ): array {
        $chunksDisk = config('chunked-upload.storage.disk', 'local_chunks');

        Log::info('Extracting video metadata from local chunks disk', [
            'localPath' => $localPath,
            'disk' => $chunksDisk,
            'sessionId' => $sessionId,
        ]);

        try {
            // Use VideoManager to create a Video object from local chunks disk
            $videoManager = app(VideoManager::class);
            $video = $videoManager->fromDisk($localPath, $chunksDisk);

            // Get all metadata from Video object
            $metadata = $video->getMetadata();

            Log::info('Video metadata extracted successfully from local file', [
                'localPath' => $localPath,
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
            Log::warning('Failed to extract video metadata from local file', [
                'localPath' => $localPath,
                'disk' => $chunksDisk,
                'error' => $e->getMessage(),
            ]);

            // Return fallback metadata
            return [
                'width' => null,
                'height' => null,
                'duration' => null,
                'size' => null,
                'format' => null,
                'aspect_ratio' => '16:9',
                'aspect_ratio_data' => null,
            ];
        }
    }
}
