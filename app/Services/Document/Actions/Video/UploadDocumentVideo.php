<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\Requests\DocumentVideoUploadRequest;
use App\Services\Document\Responses\VideoUploadResponse;
use App\Services\Upload\UploadSessionManager;
use App\Services\Upload\UploadSessionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class UploadDocumentVideo
{
    use AsAction;

    /**
     * Handle document video upload with generalized parameters.
     */
    public function handle(
        Document $document,
        UploadedFile $file,
        string $tenantId,
        ?string $sessionId = null,
        ?int $chunkIndex = null,
        ?int $totalChunks = null,
        ?string $fileName = null,
    ): array {
        try {
            // Configure session manager for this document-specific storage
            $sessionManager = UploadSessionManager::start('http', $tenantId)
                ->storeIn(
                    DocumentDirectoryManager::make($document)
                        ->videos()
                        ->getDirectory(),
                );

            // Handle chunked upload if parameters provided
            if ($sessionId && $chunkIndex !== null && $totalChunks) {
                return $this->handleChunkedUpload(
                    $sessionManager,
                    $sessionId,
                    $file,
                    $chunkIndex,
                    $totalChunks,
                    $fileName,
                    $document,
                );
            }

            // Handle direct upload
            $path = $sessionManager->upload($file);

            Log::info('Video uploaded directly', [
                'fileName' => $fileName,
                'path' => $path,
            ]);

            // Process the video file
            return ProcessDocumentVideo::run($path, $document);
        } catch (Exception $e) {
            Log::error('Video upload failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle HTTP controller request - normalize params and return JSON response.
     */
    public function asController(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            // Normalize HTTP parameters to generalized ones
            $documentModel = Document::findOrFail($document);
            $tenantId = auth()->user()->getActiveTenantId();

            // Handle chunked vs direct upload
            if ($request->isChunkedUpload()) {
                $result = $this->handle(
                    $documentModel,
                    $request->file('video'),
                    $tenantId,
                    $request->getFileKey(),
                    $request->getChunkIndex(),
                    $request->getTotalChunks(),
                    $request->getFileName(),
                );
            } else {
                $result = $this->handle(
                    $documentModel,
                    $request->file('video'),
                    $tenantId,
                );
            }

            return VideoUploadResponse::success($result);
        } catch (Exception $e) {
            Log::error('Failed to upload video', [
                'error' => $e->getMessage(),
            ]);

            return VideoUploadResponse::error('Failed to upload video. Please try again.');
        }
    }

    private function handleChunkedUpload(
        UploadSessionService $sessionManager,
        string $sessionId,
        UploadedFile $file,
        int $chunkIndex,
        int $totalChunks,
        ?string $fileName,
        Document $document,
    ): array {
        $sessionManager->storeChunk($sessionId, $file, $chunkIndex);

        Log::info('Chunk uploaded successfully', [
            'sessionId' => $sessionId,
            'chunk' => $chunkIndex,
            'totalChunks' => $totalChunks,
        ]);

        // Check if all chunks are uploaded
        if ($sessionManager->isComplete($sessionId, $totalChunks)) {
            // Assemble all chunks into final file
            $finalPath = $sessionManager->assembleFile(
                $sessionId,
                $fileName ?? 'video.mp4',
                $totalChunks,
            );

            // Clean up chunk files
            $sessionManager->cleanupSession($sessionId);

            // Return success immediately and process video in background
            ProcessDocumentVideo::dispatch($finalPath, $document);

            Log::info('HTTP upload session completed', [
                'sessionId' => $sessionId,
                'result' => [
                    'path' => $finalPath,
                ],
            ]);

            return [
                'success' => true,
                'completed' => true,
                'filename' => basename($finalPath),
                'message' => 'Video uploaded successfully. Processing in background.',
                'processing' => true,
            ];
        }

        // Return chunk upload success response
        return VideoUploadResponse::chunkProgress($chunkIndex, $totalChunks)->getData(true);
    }
}
