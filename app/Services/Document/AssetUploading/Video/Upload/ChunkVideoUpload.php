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
        UploadedFile $file,
        int $chunkIndex,
        int $totalChunks,
        ?string $fileName,
        Document $document,
        ?string $videoSessionId = null,
    ): array {
        try {
            // Store the chunk
            $sessionManager->storeChunk($sessionId, $file, $chunkIndex);

            Log::info('Chunk uploaded successfully', [
                'sessionId' => $sessionId,
                'chunk' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'documentId' => $document->id,
            ]);

            // Update session chunk progress if we have a video upload session
            if ($videoSessionId) {
                VideoUploadSessionManager::updateChunkProgress(
                    $videoSessionId,
                    $chunkIndex + 1,
                    $totalChunks,
                );
            }

            // Check if all chunks are uploaded
            if ($sessionManager->isComplete($sessionId, $totalChunks)) {
                // Update session to show upload complete and processing starting
                if ($videoSessionId) {
                    VideoUploadSessionManager::update($videoSessionId, [
                        'upload_progress' => 100,
                        'phase' => 'chunk_assembly',
                        'status' => 'processing',
                    ]);
                }

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
                    'message' => 'All chunks uploaded successfully. Processing started.',
                    'processing' => true,
                ];
            }

            // Return chunk upload progress response
            return [
                'success' => true,
                'completed' => false,
                'chunk' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'progress' => round((($chunkIndex + 1) / $totalChunks) * 100, 2),
                'message' => "Chunk {$chunkIndex} of {$totalChunks} uploaded successfully.",
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

            // Configure session manager for this document-specific storage
            $sessionManager = UploadSessionManager::start('http', $tenantId)->storeIn(
                DocumentDirectoryManager::make($documentModel)->videos()->getDirectory(),
            );

            // Get video session ID if available
            $videoSessionId = $request->input('session_id');

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
     * Dispatch assembly of chunks asynchronously to avoid HTTP timeout.
     * Chain with FinalizeVideoUpload and tag with session ID for cancellation.
     */
    private function assembleChunksAsync(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        ?string $videoSessionId = null,
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
