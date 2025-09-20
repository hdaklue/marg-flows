<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Actions\Video\AssembleVideoChunks;
use App\Services\Document\Requests\DocumentVideoUploadRequest;
use App\Services\Document\Responses\VideoUploadResponse;
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
        // Log::info('ChunkVideoUpload::asController called', [
        //     'documentId' => $document,
        //     'hasSessionId' => $request->has('session_id'),
        //     'sessionId' => $request->input('session_id'),
        //     'chunkIndex' => $request->getChunkIndex(),
        //     'totalChunks' => $request->getTotalChunks(),
        // ]);

        try {
            $documentModel = Document::findOrFail($document);
            $tenantId = auth()->user()->getActiveTenantId();

            // Configure session manager for this document-specific storage
            $sessionManager = UploadSessionManager::start('http', $tenantId)->storeIn(
                DocumentDirectoryManager::make($documentModel)->videos()->getDirectory(),
            );

            // Get video session ID if available
            $videoSessionId = $request->input('session_id');

            // // Debug logging for session tracking
            // if ($videoSessionId) {
            //     Log::info('ChunkVideoUpload processing chunk', [
            //         'chunkIndex' => $request->getChunkIndex(),
            //         'totalChunks' => $request->getTotalChunks(),
            //         'videoSessionId' => $videoSessionId,
            //     ]);
            // }

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
     * Chain with ProcessDocumentVideo and tag with session ID for cancellation.
     */
    private function assembleChunksAsync(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        ?string $videoSessionId = null,
    ): void {
        // Dispatch assembly job - it will handle sequential ProcessDocumentVideo dispatch
        AssembleVideoChunks::dispatch(
            $sessionManager,
            $sessionId,
            $fileName,
            $totalChunks,
            $document,
            $videoSessionId,
        );

        Log::info('Video assembly job dispatched', [
            'sessionId' => $sessionId,
            'videoSessionId' => $videoSessionId,
        ]);
    }

    /**
     * Assemble all uploaded chunks into final video file.
     */
    private function assembleChunks(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        ?string $videoSessionId = null,
    ): array {
        try {
            // Assemble all chunks into final file
            $finalPath = $sessionManager->assembleFile(
                $sessionId,
                $fileName ?? 'video.mp4',
                $totalChunks,
            );

            // Clean up chunk files
            $sessionManager->cleanupSession($sessionId);

            Log::info('Chunked upload session completed', [
                'sessionId' => $sessionId,
                'finalPath' => $finalPath,
                'documentId' => $document->id,
            ]);

            // Mark upload complete and start processing if we have a video session
            if ($videoSessionId) {
                VideoUploadSessionManager::startProcessing($videoSessionId, basename($finalPath));
                // Dispatch processing job with session ID
                ProcessDocumentVideo::dispatch($finalPath, $document, $videoSessionId);
            } else {
                // Fallback for old behavior without session tracking
                ProcessDocumentVideo::dispatch($finalPath, $document);
            }

            return [
                'completed' => true,
                'filename' => basename($finalPath),
                'thumbnail' => null,
                'message' => 'Video uploaded successfully. Processing in background.',
                'processing' => true,
            ];
        } catch (Exception $e) {
            Log::error('Failed to assemble chunks', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId,
                'documentId' => $document->id,
            ]);

            // Cleanup on failure
            try {
                $sessionManager->cleanupSession($sessionId);
            } catch (Exception $cleanupError) {
                Log::warning('Failed to cleanup session after assembly failure', [
                    'sessionId' => $sessionId,
                    'cleanupError' => $cleanupError->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}
