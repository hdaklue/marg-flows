<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video;

use App\Models\Document;
use App\Services\Document\HTTP\Requests\CreateVideoUploadSessionRequest;
use App\Services\Document\HTTP\Responses\VideoUploadResponse;
use App\Services\Document\Sessions\Enums\VideoUploadType;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateVideoUploadSession
{
    use AsAction;

    /**
     * Create a new video upload session.
     */
    public function handle(
        Document $document,
        string $originalFilename,
        int $fileSize,
        int $maxSingleFileSize,
        null|int $chunksTotal = null,
    ): array {
        // Determine upload type based on file size
        $uploadType = $fileSize >= $maxSingleFileSize
            ? VideoUploadType::CHUNK
            : VideoUploadType::SINGLE;

        $sessionId = VideoUploadSessionManager::create(
            $document,
            $originalFilename,
            $fileSize,
            $uploadType,
            $chunksTotal,
        );

        return [
            'session_id' => $sessionId,
            'upload_type' => $uploadType->value,
            'file_size' => $fileSize,
            'chunks_total' => $chunksTotal,
            'max_single_file_size' => $maxSingleFileSize,
        ];
    }

    /**
     * Handle HTTP controller request to create upload session.
     */
    public function asController(
        CreateVideoUploadSessionRequest $request,
        string $document,
    ): JsonResponse {
        $documentModel = Document::findOrFail($document);

        // Get file info from request
        $fileSize = $request->getFileSize();
        $originalFilename = $request->getOriginalFilename();
        $maxSingleFileSize = $request->getMaxSingleFileSize();
        $chunksTotal = $request->getChunksTotal();

        $result = $this->handle(
            $documentModel,
            $originalFilename,
            $fileSize,
            $maxSingleFileSize,
            $chunksTotal,
        );

        return VideoUploadResponse::sessionCreated($result);
    }
}
