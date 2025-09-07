<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Upload\Facades\UploadSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UploadProgressController extends Controller
{
    /**
     * Get upload progress for a session
     */
    public function show(Request $request, string $sessionId): JsonResponse
    {
        $progress = UploadSessionManager::driver('redis')->getProgress(
            $sessionId,
        );

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
        UploadSessionManager::driver('redis')->cleanupSession($sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Upload session cleaned up',
        ]);
    }
}
