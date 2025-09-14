<?php

declare(strict_types=1);

namespace App\Services\Upload\Strategies\Progress;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\DTOs\ProgressData;
use Illuminate\Support\Facades\Cache;
use Log;

final class HttpResponseProgressStrategy implements ProgressStrategyContract
{
    public function init(string $sessionId, array $metadata): void
    {
        // Store session metadata in cache for HTTP progress tracking
        Cache::put(
            "upload_session_{$sessionId}",
            [
                'status' => 'initialized',
                'metadata' => $metadata,
                'created_at' => now(),
            ],
            now()->addHours(2),
        );

        Log::info('HTTP upload session initialized', [
            'sessionId' => $sessionId,
            'metadata' => $metadata,
        ]);
    }

    public function updateProgress(
        string $sessionId,
        ProgressData $progress,
    ): void {
        // Update progress in cache - can be retrieved via API endpoint
        $sessionData = Cache::get("upload_session_{$sessionId}", []);
        $sessionData['progress'] = $progress->toArray();
        $sessionData['status'] = $progress->status;
        $sessionData['updated_at'] = now();

        Cache::put(
            "upload_session_{$sessionId}",
            $sessionData,
            now()->addHours(2),
        );

        Log::info('HTTP upload progress updated', [
            'sessionId' => $sessionId,
            'progress' => $progress->toArray(),
        ]);
    }

    public function complete(string $sessionId, mixed $result): void
    {
        // Mark session as completed in cache
        $sessionData = Cache::get("upload_session_{$sessionId}", []);
        $sessionData['status'] = 'completed';
        $sessionData['result'] = $result;
        $sessionData['completed_at'] = now();

        Cache::put(
            "upload_session_{$sessionId}",
            $sessionData,
            now()->addHours(2),
        );

        Log::info('HTTP upload session completed', [
            'sessionId' => $sessionId,
            'result' => $result,
        ]);
    }

    public function error(string $sessionId, string $message): void
    {
        // Mark session as failed in cache
        $sessionData = Cache::get("upload_session_{$sessionId}", []);
        $sessionData['status'] = 'failed';
        $sessionData['error'] = $message;
        $sessionData['failed_at'] = now();

        Cache::put(
            "upload_session_{$sessionId}",
            $sessionData,
            now()->addHours(2),
        );

        Log::error('HTTP upload session failed', [
            'sessionId' => $sessionId,
            'error' => $message,
        ]);
    }

    public function getProgress(string $sessionId): ?ProgressData
    {
        $sessionData = Cache::get("upload_session_{$sessionId}");

        if (! $sessionData || ! isset($sessionData['progress'])) {
            return null;
        }

        $progressArray = $sessionData['progress'];

        return new ProgressData(
            completedChunks: $progressArray['completedChunks'] ?? 0,
            totalChunks: $progressArray['totalChunks'] ?? 0,
            bytesUploaded: $progressArray['bytesUploaded'] ?? 0,
            totalBytes: $progressArray['totalBytes'] ?? 0,
            percentage: $progressArray['percentage'] ?? 0,
            status: $progressArray['status'] ?? 'unknown',
            currentChunk: $progressArray['currentChunk'] ?? null,
        );
    }

    public function cleanup(string $sessionId): void
    {
        Cache::forget("upload_session_{$sessionId}");

        Log::info('HTTP upload session cleaned up', [
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * Get raw session data for HTTP response.
     */
    public function getSessionData(string $sessionId): ?array
    {
        return Cache::get("upload_session_{$sessionId}");
    }
}
