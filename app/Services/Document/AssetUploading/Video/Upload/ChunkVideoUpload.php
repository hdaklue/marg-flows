<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video\Upload;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\HTTP\Requests\DocumentVideoUploadRequest;
use App\Services\Document\HTTP\Responses\VideoUploadResponse;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use App\Services\Upload\UploadSessionManager;
use App\Services\Upload\UploadSessionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class ChunkVideoUpload
{
    use AsAction;

    /**
     * Handle chunked video upload for a document.
     */
    // this should be VideoUploadSessionManager
    public function handle(
        UploadSessionService $sessionManager,
        string $sessionId,
        UploadedFile $chunk,
        int $chunkIndex,
        int $totalChunks,
        ?string $fileName,
        Document $document,
        string $videoSessionId,
    ): array {
        try {
            // Store the chunk
            $sessionManager->storeChunk($sessionId, $chunk, $chunkIndex);

            // Update session chunk progress
            VideoUploadSessionManager::updateChunkProgress(
                $videoSessionId,
                $chunkIndex + 1,
                $totalChunks,
            );

            // Get detailed progress data from the upload session
            $progressData = $sessionManager->getProgress($sessionId);

            // Check if all chunks are uploaded
            if ($sessionManager->isComplete($sessionId, $totalChunks)) {
                return $this->handleChunksComplete(
                    $sessionManager,
                    $sessionId,
                    $fileName,
                    $totalChunks,
                    $document,
                    $chunkIndex,
                    $videoSessionId,
                );
            }

            // Return chunk upload progress response with detailed progress data
            return [
                'success' => true,
                'completed' => false,
                'chunk' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'progress' => $progressData?->percentage ?? round((($chunkIndex + 1) / $totalChunks) * 100, 2),
                'message' => "Chunk {$chunkIndex} of {$totalChunks} uploaded successfully.",
                'progressData' => $progressData?->toArray(),
                'bytesUploaded' => $progressData?->bytesUploaded,
                'totalBytes' => $progressData?->totalBytes,
                'estimatedTimeRemaining' => $progressData?->estimatedTimeRemaining,
            ];
        } catch (Exception $e) {
            Log::error('Chunk upload failed', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId,
                'chunkIndex' => $chunkIndex,
                'documentId' => $document->id,
            ]);

            throw $e;
        }
    }

    /**
     * Handle HTTP controller request for chunked video upload.
     */
    public function asController(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            $documentModel = Document::findOrFail($document);
            $tenantId = auth()->user()->getActiveTenantId();

            // Get video session ID (required for video uploads)
            $videoSessionId = $request->input('session_id');
            if (! $videoSessionId) {
                return VideoUploadResponse::error('Video session ID is required for chunk uploads.');
            }

            // Configure session manager for this document-specific storage
            // Use existing session or create new one if needed
            $sessionManager = UploadSessionManager::start('http', $tenantId)->storeIn(
                DocumentDirectoryManager::make($documentModel)->videos()->getDirectory(),
            );

            $result = $this->handle(
                $sessionManager,
                $request->getFileKey(),
                $request->file('video'),
                $request->getChunkIndex(),
                $request->getTotalChunks(),
                $request->getFileName(),
                $documentModel,
                $videoSessionId,
            );

            return VideoUploadResponse::success($result);
        } catch (Exception $e) {
            Log::error('Chunk video upload failed', [
                'error' => $e->getMessage(),
                'documentId' => $document,
                'chunkIndex' => $request->getChunkIndex() ?? 'unknown',
            ]);

            return VideoUploadResponse::error('Chunk video upload failed. Please try again.');
        }
    }

    /**
     * Handle completion when all chunks have been uploaded.
     */
    private function handleChunksComplete(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        int $chunkIndex,
        string $videoSessionId,
    ): array {
        // Update session to show upload complete and processing starting
        VideoUploadSessionManager::update($videoSessionId, [
            'upload_progress' => 100,
            'phase' => 'chunk_assembly',
            'status' => 'processing',
        ]);

        // Dispatch assembly asynchronously to avoid HTTP timeout
        $this->assembleChunksAsync(
            $sessionManager,
            $sessionId,
            $fileName,
            $totalChunks,
            $document,
            $videoSessionId,
        );

        // Return success immediately - polling will handle the rest
        return [
            'success' => true,
            'completed' => true,
            'chunk' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'progress' => 100,
            'phase' => 'chunk_assembly',
            'status' => 'processing',
            'message' => 'All chunks uploaded successfully. Processing started.',
            'processing' => true,
        ];
    }

    /**
     * Dispatch assembly of chunks asynchronously to avoid HTTP timeout.
     * Chain with FinalizeVideoUpload and tag with session ID for cancellation.
     */
    private function assembleChunksAsync(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        string $videoSessionId,
    ): void {
        // Dispatch assembly job with a small delay to ensure chunks are fully written
        AssembleVideoChunks::dispatch(
            $sessionManager,
            $sessionId,
            $fileName,
            $totalChunks,
            $document,
            $videoSessionId,
        )
            ->delay(now()->addSeconds(3))
            ->onQueue('document-video-upload');

        Log::info('Video assembly job dispatched', [
            'sessionId' => $sessionId,
            'videoSessionId' => $videoSessionId,
        ]);
    }
}
