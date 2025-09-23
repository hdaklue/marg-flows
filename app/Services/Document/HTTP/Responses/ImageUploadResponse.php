<?php

declare(strict_types=1);

namespace App\Services\Document\HTTP\Responses;

use Illuminate\Http\JsonResponse;

final class ImageUploadResponse
{
    /**
     * Create a successful image upload response.
     */
    public static function success(string $url, string $filename): JsonResponse
    {
        return response()->json([
            'success' => 1,
            'file' => [
                'url' => $url,
                'filename' => $filename,
            ],
        ]);
    }

    /**
     * Create an error response with validation errors.
     */
    public static function validationError(string $message, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => 0,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Create a general error response.
     */
    public static function error(string $message = 'Failed to upload image'): JsonResponse
    {
        return response()->json([
            'success' => 0,
            'message' => $message,
        ], 500);
    }
}
