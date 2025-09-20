<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
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

final class SingleVideoUpload
{
    use AsAction;

    /**
     * Handle single (non-chunked) video upload for a document.
     */
    public function handle(
        UploadSessionService $sessionManager,
        UploadedFile $file,
        Document $document,
        ?string $fileName = null,
    ): array {
        try {
            // Upload the file directly
            $path = $sessionManager->upload($file);

            Log::info('Video uploaded directly', [
                'originalName' => $file->getClientOriginalName(),
                'fileName' => $fileName,
                'path' => $path,
                'size' => $file->getSize(),
                'documentId' => $document->id,
            ]);

            // Process the video file synchronously for direct uploads
            $result = ProcessDocumentVideo::run($path, $document);

            return [
                'completed' => true,
                'filename' => $result['file']['filename'] ?? basename($path),
                'thumbnail' => $result['file']['thumbnail'] ?? null,
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'duration' => $result['duration'] ?? null,
                'size' => $result['size'] ?? $file->getSize(),
                'format' => $result['format'] ?? null,
                'aspect_ratio' => $result['aspect_ratio'] ?? '16:9',
                'aspect_ratio_data' => $result['aspect_ratio_data'] ?? null,
                'message' => 'Video uploaded and processed successfully.',
                'processing' => false,
            ];
        } catch (Exception $e) {
            Log::error('Single video upload failed', [
                'error' => $e->getMessage(),
                'fileName' => $fileName,
                'originalName' => $file->getClientOriginalName(),
                'documentId' => $document->id,
            ]);

            throw $e;
        }
    }

    /**
     * Handle HTTP controller request for single video upload.
     */
    public function asController(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            $documentModel = Document::findOrFail($document);
            $tenantId = auth()->user()->getActiveTenantId();
            $sessionId = $request->input('session_id');

            if (! $sessionId || ! VideoUploadSessionManager::exists($sessionId)) {
                return VideoUploadResponse::error('Invalid or expired upload session.', 400);
            }

            // Configure session manager for this document-specific storage
            $sessionManager = UploadSessionManager::start('http', $tenantId)->storeIn(
                DocumentDirectoryManager::make($documentModel)->videos()->getDirectory(),
            );

            $result = $this->handle(
                $sessionManager,
                $request->file('video'),
                $documentModel,
                $request->getFileName(),
            );

            // Mark upload complete and start processing
            VideoUploadSessionManager::startProcessing($sessionId, $result['filename']);

            // Dispatch processing job with session ID
            ProcessDocumentVideo::dispatch($result['filename'], $documentModel, $sessionId);

            return response()->json([
                'success' => true,
                'message' => 'Upload complete, processing started',
            ]);
        } catch (Exception $e) {
            Log::error('Single video upload failed', [
                'error' => $e->getMessage(),
                'documentId' => $document,
            ]);

            // Mark session as failed if we have session ID
            $sessionId = $request->input('session_id');
            if ($sessionId) {
                VideoUploadSessionManager::fail($sessionId, $e->getMessage());
            }

            return VideoUploadResponse::error('Single video upload failed. Please try again.');
        }
    }
}
