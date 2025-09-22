<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use App\Services\Upload\UploadSessionService;
use App\Services\Video\Resolutions\Resolution480p;
use App\Services\Video\Services\ResolutionExporter;
use App\Services\Video\VideoManager;
use Exception;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class AssembleVideoChunks
{
    use AsAction;

    public int $tries = 3;

    public int $timeout = 30 * 60; // 30 minutes

    /**
     * Assemble uploaded video chunks and start processing.
     */
    public function handle(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        ?string $videoSessionId = null,
    ): void {
        try {
            Log::info('Starting video chunk assembly', [
                'sessionId' => $sessionId,
                'videoSessionId' => $videoSessionId,
                'fileName' => $fileName,
                'totalChunks' => $totalChunks,
                'documentId' => $document->id,
            ]);

            // Assemble all chunks into final file (stored locally)
            $localFinalPath = $sessionManager->assembleFile(
                $sessionId,
                $fileName ?? 'video.mp4',
                $totalChunks,
            );

            // Convert video locally before moving to remote storage
            $videoMetadata = null;
            if ($videoSessionId && config('video-upload.processing.extract_metadata', true)) {
                try {
                    Log::info('Starting local video conversion and metadata extraction', [
                        'localPath' => $localFinalPath,
                        'videoSessionId' => $videoSessionId,
                    ]);

                    // Perform 480p conversion - overwrite the original local file
                    $chunksDisk = config('chunked-upload.storage.disk');

                    Log::info('Video conversion setup', [
                        'localFinalPath' => $localFinalPath,
                        'chunksDisk' => $chunksDisk,
                        'willOverwriteOriginal' => true,
                    ]);

                    // Use ResolutionExporter directly to specify exact output path (overwrite original)
                    $exporter = ResolutionExporter::start($localFinalPath, $chunksDisk);

                    // Create video object to get orientation for 480p conversion
                    $videoManager = app(VideoManager::class);
                    $video = $videoManager->fromDisk($localFinalPath, $chunksDisk);
                    $orientation = $video->getOrientation();

                    // Create 480p conversion and export to temporary file with _mod suffix
                    $pathInfo = pathinfo($localFinalPath);
                    $tempConvertedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_mod.' . $pathInfo['extension'];

                    $conversion = new Resolution480p($orientation);
                    $conversionResult = $exporter->export($conversion, $tempConvertedPath);
                    $conversionResults = [$conversionResult];

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
                        'conversionResults' => $conversionResults,
                        'successfulConversions' => count(array_filter($conversionResults, fn ($result) => $result->isSuccessful())),
                    ]);

                    // Extract metadata from the converted local file
                    $videoMetadata = $this->extractMetadataFromLocalFile(
                        $localFinalPath,
                        $videoSessionId,
                    );

                    // Update session with metadata
                    VideoUploadSessionManager::updateProcessingMetadata(
                        $videoSessionId,
                        'metadata',
                        $videoMetadata,
                    );
                } catch (Exception $e) {
                    Log::warning('Failed to convert video or extract metadata', [
                        'localPath' => $localFinalPath,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Move converted file to document storage (remote)
            $remoteFinalPath = $this->moveToDocumentStorage($localFinalPath, $document);

            // Clean up local files (chunks and assembled file)
            $sessionManager->cleanupSession($sessionId);
            $this->cleanupLocalFile($localFinalPath);

            Log::info('Video chunk assembly completed', [
                'sessionId' => $sessionId,
                'localPath' => $localFinalPath,
                'remotePath' => $remoteFinalPath,
                'documentId' => $document->id,
            ]);

            // Mark upload complete and start processing if we have a video session
            if ($videoSessionId) {
                Log::info('Assembly completed, dispatching ProcessDocumentVideo sequentially', [
                    'videoSessionId' => $videoSessionId,
                    'finalFilename' => basename($remoteFinalPath),
                    'remotePath' => $remoteFinalPath,
                    'documentId' => $document->id,
                ]);

                VideoUploadSessionManager::startProcessing(
                    $videoSessionId,
                    basename($remoteFinalPath),
                );

                // Dispatch processing job with session ID - this runs after assembly completes
                ProcessDocumentVideo::dispatch($remoteFinalPath, $document, $videoSessionId)->onQueue('document-video-upload');
            } else {
                Log::info('No video session ID provided, using fallback processing', [
                    'remotePath' => $remoteFinalPath,
                    'documentId' => $document->id,
                ]);

                // Fallback for old behavior without session tracking
                ProcessDocumentVideo::dispatch($remoteFinalPath, $document)->onQueue('document-video-upload');
            }
        } catch (Exception $e) {
            Log::error('Video chunk assembly failed', [
                'sessionId' => $sessionId,
                'videoSessionId' => $videoSessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'documentId' => $document->id,
                'fileName' => $fileName,
                'totalChunks' => $totalChunks,
            ]);

            // Mark session as failed
            if ($videoSessionId) {
                VideoUploadSessionManager::fail(
                    $videoSessionId,
                    'Failed to assemble video chunks: ' . $e->getMessage(),
                );
            }

            throw $e;
        }
    }

    /**
     * Move assembled file from local storage to document storage (remote).
     */
    private function moveToDocumentStorage(string $localPath, Document $document): string
    {
        $chunksDisk = config('chunked-upload.storage.disk', 'local_chunks');
        $documentDisk = config('document.storage.disk', 'do_spaces');

        // Get document directory path
        $documentDirectory = DocumentDirectoryManager::make($document)->videos()->getDirectory();
        $remotePath = $documentDirectory . '/' . basename($localPath);

        Log::info('Starting file transfer to remote storage', [
            'localPath' => $localPath,
            'remotePath' => $remotePath,
            'documentId' => $document->id,
            'fileSize' => Storage::disk($chunksDisk)->size($localPath),
        ]);

        $startTime = microtime(true);

        // Read from local chunks disk and write to document disk using streaming
        $localStream = Storage::disk($chunksDisk)->readStream($localPath);
        if (! $localStream) {
            throw new Exception("Failed to read assembled file from local storage: {$localPath}");
        }

        try {
            $success = Storage::disk($documentDisk)->writeStream($remotePath, $localStream);
            if (! $success) {
                throw new Exception(
                    "Failed to upload assembled file to document storage: {$remotePath}",
                );
            }
        } finally {
            if (is_resource($localStream)) {
                fclose($localStream);
            }
        }

        $transferTime = microtime(true) - $startTime;

        Log::info('Completed file transfer to remote storage', [
            'localPath' => $localPath,
            'remotePath' => $remotePath,
            'documentId' => $document->id,
            'transferTimeSeconds' => round($transferTime, 2),
        ]);

        return $remotePath;
    }

    /**
     * Clean up local assembled file.
     */
    private function cleanupLocalFile(string $localPath): void
    {
        $chunksDisk = config('chunked-upload.storage.disk', 'local_chunks');

        try {
            if (Storage::disk($chunksDisk)->exists($localPath)) {
                Storage::disk($chunksDisk)->delete($localPath);
                Log::info('Cleaned up local assembled file', ['path' => $localPath]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to cleanup local assembled file', [
                'path' => $localPath,
                'error' => $e->getMessage(),
            ]);
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
