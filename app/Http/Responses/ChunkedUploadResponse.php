<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ChunkedUploadResponse
{
    /**
     * Return a successful upload response.
     */
    public static function success(array $data = []): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => 'Upload completed successfully',
                'data' => $data,
            ],
            200,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Return a successful chunk upload response.
     */
    public static function chunkSuccess(array $data = []): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => 'Chunk uploaded successfully',
                'data' => $data,
            ],
            200,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Return a final assembly success response.
     */
    public static function assemblySuccess(array $fileData): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => 'File assembled successfully',
                'data' => $fileData,
            ],
            200,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Return a validation error response.
     */
    public static function validationError(array $errors): JsonResponse
    {
        return response()->json(
            [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ],
            422,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Return a generic error response.
     */
    public static function error(
        string $message,
        int $statusCode = 500,
        array $data = [],
    ): JsonResponse {
        return response()->json(
            [
                'success' => false,
                'message' => $message,
                'data' => $data,
            ],
            $statusCode,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Return a cancellation success response.
     */
    public static function cancelled(): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => 'Upload cancelled and chunks cleaned up',
            ],
            200,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Return a deletion success response.
     */
    public static function deleted(): JsonResponse
    {
        return response()->json(
            [
                'success' => true,
                'message' => 'File deleted successfully',
            ],
            200,
            [],
            JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Check if the request expects JSON response.
     */
    public static function expectsJson(): bool
    {
        return
            request()->expectsJson()
            || request()->ajax()
            || request()->header('Content-Type') === 'application/json'
            || str_contains(request()->header('Accept', ''), 'application/json');
    }
}
