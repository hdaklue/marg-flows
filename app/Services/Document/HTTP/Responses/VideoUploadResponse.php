<?php

declare(strict_types=1);

namespace App\Services\Document\HTTP\Responses;

use App\Services\Document\Sessions\Enums\VideoUploadType;
use Illuminate\Http\JsonResponse;

final class VideoUploadResponse
{
    /**
     * Create a successful video upload response.
     */
    public static function success(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'completed' => $data['completed'] ?? true,
            'fileKey' => $data['fileKey'] ?? null,
            'file' => [
                'filename' => $data['filename'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
            ],
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'duration' => $data['duration'] ?? null,
            'size' => $data['size'] ?? null,
            'format' => $data['format'] ?? null,
            'original_format' => $data['original_format'] ?? null,
            'aspect_ratio' => $data['aspect_ratio'] ?? '16:9',
            'aspect_ratio_data' => $data['aspect_ratio_data'] ?? null,
            'conversion' => $data['conversion'] ?? [
                'performed' => false,
                'success' => true,
                'message' => 'No conversion needed',
            ],
            'message' => $data['message'] ?? 'Video uploaded successfully',
        ]);
    }

    /**
     * Create a chunk upload progress response.
     */
    public static function chunkProgress(
        int $chunkIndex,
        int $totalChunks,
        string $message = 'Chunk uploaded successfully',
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'completed' => false,
            'chunk' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'message' => $message,
        ]);
    }

    /**
     * Create an error response.
     */
    public static function error(
        string $message = 'Failed to upload video',
        int $statusCode = 500,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Create session status response with {status, phase, data} structure.
     */
    public static function sessionStatus(
        string $status,
        string $phase,
        array $data,
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'phase' => $phase,
            'data' => $data,
        ]);
    }

    /**
     * Create session creation response.
     */
    public static function sessionCreated(array $sessionData): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'uploading',
            'phase' => VideoUploadType::tryFrom($sessionData['upload_type'])?->getInitialPhase()->value ?? 'single_upload',
            'data' => [
                'session_id' => $sessionData['session_id'],
                'upload_type' => $sessionData['upload_type'],
                'file_size' => $sessionData['file_size'],
                'chunks_total' => $sessionData['chunks_total'],
                'max_single_file_size' => $sessionData['max_single_file_size'],
            ],
        ]);
    }
}
