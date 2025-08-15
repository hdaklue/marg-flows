<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Upload\UploadProgressManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UploadProgressController extends Controller
{
    /**
     * Get upload progress for a session
     */
    public function show(Request $request, string $sessionId): JsonResponse
    {
        $progressStrategy = UploadProgressManager::simple();
        $progress = $progressStrategy->getProgress($sessionId);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Upload session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress->toArray(),
        ]);
    }

    /**
     * Clean up upload session
     */
    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        $progressStrategy = UploadProgressManager::simple();
        $progressStrategy->cleanup($sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Upload session cleaned up',
        ]);
    }
}