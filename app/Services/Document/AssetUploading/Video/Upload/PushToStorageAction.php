<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video\Upload;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use Exception;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class PushToStorageAction
{
    use AsAction;

    public int $tries = 3;

    public int $timeout = 10 * 60; // 10 minutes for storage upload

    public static function getJobQueue(): string
    {
        return 'document-video-upload';
    }

    /**
     * Push converted video to remote storage and clean up local files.
     */
    public function handle(
        string $localFinalPath,
        Document $document,
        ?string $videoSessionId = null,
        ?array $videoMetadata = null,
    ): string {
        try {
            Log::info('Starting push to storage', [
                'localPath' => $localFinalPath,
                'videoSessionId' => $videoSessionId,
                'documentId' => $document->id,
            ]);

            // Move converted file to document storage (remote)
            $remoteFinalPath = $this->moveToDocumentStorage($localFinalPath, $document);

            // Clean up local assembled file
            $this->cleanupLocalFile($localFinalPath);

            Log::info('Storage push completed', [
                'localPath' => $localFinalPath,
                'remotePath' => $remoteFinalPath,
                'documentId' => $document->id,
            ]);

            // Mark upload complete and start final processing if we have a video session
            if ($videoSessionId) {
                Log::info('Storage push completed, dispatching FinalizeVideoUpload', [
                    'videoSessionId' => $videoSessionId,
                    'finalFilename' => basename($remoteFinalPath),
                    'remotePath' => $remoteFinalPath,
                    'documentId' => $document->id,
                ]);

                VideoUploadSessionManager::startProcessing(
                    $videoSessionId,
                    basename($remoteFinalPath),
                );

                // Dispatch final processing job
                FinalizeVideoUpload::dispatch($remoteFinalPath, $document, $videoSessionId)->onQueue('document-video-upload');
            } else {
                Log::info('No video session ID provided, using fallback processing', [
                    'remotePath' => $remoteFinalPath,
                    'documentId' => $document->id,
                ]);

                // Fallback for old behavior without session tracking
                FinalizeVideoUpload::dispatch($remoteFinalPath, $document)->onQueue('document-video-upload');
            }

            return $remoteFinalPath;
        } catch (Exception $e) {
            Log::error('Storage push failed', [
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
                    'Failed to push to storage: ' . $e->getMessage(),
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
}
