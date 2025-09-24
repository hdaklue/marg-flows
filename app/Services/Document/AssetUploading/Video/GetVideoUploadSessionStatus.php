<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video;

use App\Services\Document\HTTP\Responses\VideoUploadResponse;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

final class GetVideoUploadSessionStatus
{
    use AsAction;

    /**
     * Get upload session status with phase-based data.
     */
    public function handle(string $sessionId): null|array
    {
        $sessionData = VideoUploadSessionManager::get($sessionId);

        if (!$sessionData) {
            return null;
        }

        // Build data payload based on current phase
        $data = $this->buildPhaseData($sessionData);

        return [
            'status' => $sessionData['status'],
            'phase' => $sessionData['phase'],
            'data' => $data,
        ];
    }

    /**
     * Handle HTTP controller request to get session status.
     */
    public function asController(string $sessionId): JsonResponse
    {
        $result = $this->handle($sessionId);

        if (!$result) {
            return VideoUploadResponse::error('Session not found or expired', 404);
        }

        return VideoUploadResponse::sessionStatus(
            $result['status'],
            $result['phase'],
            $result['data'],
        );
    }

    /**
     * Build data payload based on current phase.
     */
    private function buildPhaseData(array $sessionData): array
    {
        $baseData = [
            'session_id' => $sessionData['session_id'],
            'upload_type' => $sessionData['upload_type'],
            'original_filename' => $sessionData['original_filename'],
            'file_size' => $sessionData['file_size'],
            'created_at' => $sessionData['created_at'],
            'updated_at' => $sessionData['updated_at'],
        ];

        switch ($sessionData['phase']) {
            case 'single_upload':
                return array_merge($baseData, [
                    'upload_progress' => $sessionData['upload_progress'],
                ]);

            case 'chunk_upload':
                return array_merge($baseData, [
                    'upload_progress' => $sessionData['upload_progress'],
                    'chunks_uploaded' => $sessionData['chunks_uploaded'],
                    'chunks_total' => $sessionData['chunks_total'],
                ]);

            case 'video_processing':
                return array_merge($baseData, [
                    'upload_progress' => 100,
                    'processing_progress' => $sessionData['processing_progress'],
                    'final_filename' => $sessionData['final_filename'],
                ]);

            case 'complete':
                return array_merge($baseData, [
                    'upload_progress' => 100,
                    'processing_progress' => 100,
                    'final_filename' => $sessionData['final_filename'],
                    'thumbnail_filename' => $sessionData['thumbnail_filename'],
                    'video_metadata' => $sessionData['video_metadata'],
                ]);

            case 'error':
                return array_merge($baseData, [
                    'error_message' => $sessionData['error_message'],
                    'upload_progress' => $sessionData['upload_progress'],
                    'processing_progress' => $sessionData['processing_progress'],
                ]);

            default:
                return $baseData;
        }
    }
}
