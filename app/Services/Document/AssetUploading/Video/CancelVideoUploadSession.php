<?php

declare(strict_types=1);

namespace App\Services\Document\AssetUploading\Video;

use App\Services\Document\Sessions\VideoUploadSessionManager;
use Exception;
use Illuminate\Http\JsonResponse;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class CancelVideoUploadSession
{
    use AsAction;

    /**
     * Cancel video upload session and perform cleanup.
     */
    public function handle(string $sessionId): bool
    {
        Log::info('Cancelling video upload session', [
            'sessionId' => $sessionId,
        ]);

        // Check if session exists
        if (!VideoUploadSessionManager::exists($sessionId)) {
            Log::warning('Attempted to cancel non-existent session', [
                'sessionId' => $sessionId,
            ]);

            return false;
        }

        // Get session data before cleanup
        $sessionData = VideoUploadSessionManager::get($sessionId);

        // Mark session as cancelled
        VideoUploadSessionManager::fail($sessionId, 'Upload cancelled by user');

        // TODO: Add cleanup logic for partial uploads
        // - Cancel any running processing jobs
        // - Clean up temporary files
        // - Clean up chunks if applicable

        Log::info('Video upload session cancelled successfully', [
            'sessionId' => $sessionId,
            'phase' => $sessionData['phase'] ?? 'unknown',
        ]);

        return true;
    }

    /**
     * Handle HTTP controller request to cancel session.
     */
    public function asController(string $sessionId): JsonResponse
    {
        try {
            $cancelled = $this->handle($sessionId);

            if (!$cancelled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found or already completed',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Upload cancelled successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to cancel video upload session', [
                'sessionId' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel upload',
            ], 500);
        }
    }
}
