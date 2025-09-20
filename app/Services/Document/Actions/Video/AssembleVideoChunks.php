<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use App\Services\Upload\UploadSessionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class AssembleVideoChunks implements ShouldQueue
{
    use AsAction;

    public int $tries = 3;

    public int $timeout = 900; // 15 minutes

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

            // Move assembled file to document storage (remote)
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
                VideoUploadSessionManager::startProcessing($videoSessionId, basename($remoteFinalPath));
                // Dispatch processing job with session ID
                ProcessDocumentVideo::dispatch($remoteFinalPath, $document, $videoSessionId);
            } else {
                // Fallback for old behavior without session tracking
                ProcessDocumentVideo::dispatch($remoteFinalPath, $document);
            }
        } catch (Exception $e) {
            Log::error('Video chunk assembly failed', [
                'sessionId' => $sessionId,
                'videoSessionId' => $videoSessionId,
                'error' => $e->getMessage(),
                'documentId' => $document->id,
            ]);

            // Mark session as failed
            if ($videoSessionId) {
                VideoUploadSessionManager::fail($videoSessionId, 'Failed to assemble video chunks: ' . $e->getMessage());
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
        $documentDisk = config('directory-chunks.tenant_isolation.disk', 'do_spaces');

        // Get document directory path
        $documentDirectory = DocumentDirectoryManager::make($document)->videos()->getDirectory();
        $remotePath = $documentDirectory . '/' . basename($localPath);

        // Read from local chunks disk and write to document disk using streaming
        $localStream = Storage::disk($chunksDisk)->readStream($localPath);
        if (! $localStream) {
            throw new Exception("Failed to read assembled file from local storage: {$localPath}");
        }

        try {
            $success = Storage::disk($documentDisk)->writeStream($remotePath, $localStream);
            if (! $success) {
                throw new Exception("Failed to upload assembled file to document storage: {$remotePath}");
            }
        } finally {
            if (is_resource($localStream)) {
                fclose($localStream);
            }
        }

        Log::info('Moved assembled file to document storage', [
            'localPath' => $localPath,
            'remotePath' => $remotePath,
            'documentId' => $document->id,
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
