<?php

declare(strict_types=1);

namespace App\Services\Document\HTTP\Responses;

use Illuminate\Http\JsonResponse;

final class FileDeleteResponse
{
    /**
     * Create a successful deletion response.
     */
    public static function success(bool $deleted = true, null|string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => $message ?? ($deleted ? 'File deleted successfully' : 'File not found'),
        ]);
    }

    /**
     * Create an error response.
     */
    public static function error(string $message = 'Failed to delete file'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }
}
