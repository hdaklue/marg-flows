<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video\Upload;

use App\Models\Document;
use App\Services\Directory\Managers\DocumentDirectoryManager;
use App\Services\Document\HTTP\Requests\DocumentVideoUploadRequest;
use App\Services\Document\HTTP\Responses\VideoUploadResponse;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use Exception;
use Illuminate\Http\JsonResponse;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class SingleVideoUpload
{
    use AsAction;

    /**
     * Handle HTTP controller request for single video upload.
     */
    public function asController(
        DocumentVideoUploadRequest $request,
        string $document,
    ): JsonResponse {
        try {
            $documentModel = Document::findOrFail($document);
            $sessionId = $request->input('session_id');

            if (! $sessionId || ! VideoUploadSessionManager::exists($sessionId)) {
                return VideoUploadResponse::error('Invalid or expired upload session.', 400);
            }

            // Store single video directly to document storage disk (not chunk storage)
            $directoryManager = DocumentDirectoryManager::make($documentModel);
            $videoDirectory = $directoryManager->videos()->getDirectory();
            $documentDisk = $directoryManager->getDisk();

            // Generate unique filename
            $file = $request->file('video');
            $extension = $file->getClientOriginalExtension();
            $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;

            // Store directly to document storage disk
            $storedPath = $file->storeAs($videoDirectory, $uniqueFileName, $documentDisk);

            Log::info('Single video stored directly to document disk', [
                'originalName' => $file->getClientOriginalName(),
                'fileName' => $uniqueFileName,
                'path' => $storedPath,
                'disk' => $documentDisk,
                'size' => $file->getSize(),
                'documentId' => $documentModel->id,
            ]);

            $result = [
                'filename' => $uniqueFileName,
                'path' => $storedPath,
                'size' => $file->getSize(),
            ];

            // Mark upload complete and start processing
            VideoUploadSessionManager::startProcessing($sessionId, $result['filename']);

            // Dispatch processing job with full path so it can find the file in DigitalOcean Spaces
            FinalizeVideoUpload::dispatch($result['path'], $documentModel, $sessionId)->onQueue('document-video-upload');

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
