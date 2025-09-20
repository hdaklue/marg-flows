<?php

declare(strict_types=1);

namespace App\Services\Document\Sessions;

use App\Models\Document;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class VideoUploadSessionManager
{
    private const CACHE_PREFIX = 'video_upload_session:';

    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Create a new upload session.
     */
    public static function create(
        Document $document,
        string $originalFilename,
        int $fileSize,
        string $uploadType = 'single',
        ?int $chunksTotal = null,
    ): string {
        $sessionId = Str::ulid()->toString();

        $sessionData = [
            'session_id' => $sessionId,
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->getActiveTenantId(),
            'status' => 'uploading',
            'phase' => $uploadType === 'chunk' ? 'chunk_upload' : 'single_upload',
            'upload_type' => $uploadType,
            'original_filename' => $originalFilename,
            'file_size' => $fileSize,
            'chunks_total' => $chunksTotal,
            'chunks_uploaded' => 0,
            'upload_progress' => 0,
            'processing_progress' => 0,
            'final_filename' => null,
            'thumbnail_filename' => null,
            'video_metadata' => null,
            'final_data' => null,
            'error_message' => null,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        Cache::put(
            self::CACHE_PREFIX . $sessionId,
            $sessionData,
            self::DEFAULT_TTL,
        );

        return $sessionId;
    }

    /**
     * Get session data by session ID.
     */
    public static function get(string $sessionId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $sessionId);
    }

    /**
     * Update session data.
     */
    public static function update(string $sessionId, array $updates): bool
    {
        $sessionData = self::get($sessionId);
        if (! $sessionData) {
            return false;
        }

        $sessionData = array_merge($sessionData, $updates);
        $sessionData['updated_at'] = now()->toISOString();

        Cache::put(
            self::CACHE_PREFIX . $sessionId,
            $sessionData,
            self::DEFAULT_TTL,
        );

        return true;
    }

    /**
     * Update upload progress for chunk uploads.
     */
    public static function updateChunkProgress(
        string $sessionId,
        int $chunksUploaded,
        int $chunksTotal,
    ): bool {
        $progress = $chunksTotal > 0 ? (int) round(($chunksUploaded / $chunksTotal) * 100) : 0;

        // \Log::info('Updating chunk progress', [
        //     'sessionId' => $sessionId,
        //     'chunksUploaded' => $chunksUploaded,
        //     'chunksTotal' => $chunksTotal,
        //     'progress' => $progress,
        // ]);

        return self::update($sessionId, [
            'chunks_uploaded' => $chunksUploaded,
            'upload_progress' => $progress,
        ]);
    }

    /**
     * Mark upload as complete and start processing.
     */
    public static function startProcessing(string $sessionId, string $finalFilename): bool
    {
        Log::info('VideoUploadSessionManager: Starting processing phase', [
            'sessionId' => $sessionId,
            'finalFilename' => $finalFilename,
        ]);

        $result = self::update($sessionId, [
            'status' => 'processing',
            'phase' => 'video_processing',
            'upload_progress' => 100,
            'final_filename' => $finalFilename,
        ]);

        Log::info('VideoUploadSessionManager: Processing phase update result', [
            'sessionId' => $sessionId,
            'updateResult' => $result,
        ]);

        return $result;
    }

    /**
     * Update processing progress.
     */
    public static function updateProcessingProgress(
        string $sessionId,
        int $progress,
    ): bool {
        return self::update($sessionId, [
            'processing_progress' => min(100, max(0, $progress)),
        ]);
    }

    /**
     * Update processing metadata during video processing.
     */
    public static function updateProcessingMetadata(
        string $sessionId,
        string $type,
        mixed $data,
    ): bool {
        $sessionData = self::get($sessionId);
        if (! $sessionData) {
            return false;
        }

        $updates = [];

        if ($type === 'metadata') {
            $updates['video_metadata'] = $data;
            $updates['processing_progress'] = 75; // Metadata extraction is 75% of processing
        } elseif ($type === 'thumbnail') {
            $updates['thumbnail_filename'] = $data;
            $updates['processing_progress'] = 100; // Thumbnail generation completes processing
        }

        return self::update($sessionId, $updates);
    }

    /**
     * Mark session as completed with final data.
     */
    public static function complete(
        string $sessionId,
        array $finalData,
    ): bool {
        $updates = [
            'status' => 'completed',
            'phase' => 'complete',
            'processing_progress' => 100,
            'final_data' => $finalData,
            'completed_at' => now()->toISOString(),
        ];

        // Extract individual fields that GetVideoUploadSessionStatus expects
        if (isset($finalData['thumbnail'])) {
            $updates['thumbnail_filename'] = $finalData['thumbnail'];
        }

        // Build video metadata from final data
        $videoMetadata = [];
        if (isset($finalData['width'])) {
            $videoMetadata['width'] = $finalData['width'];
        }
        if (isset($finalData['height'])) {
            $videoMetadata['height'] = $finalData['height'];
        }
        if (isset($finalData['duration'])) {
            $videoMetadata['duration'] = $finalData['duration'];
        }
        if (isset($finalData['format'])) {
            $videoMetadata['format'] = $finalData['format'];
        }
        if (isset($finalData['aspect_ratio'])) {
            $videoMetadata['aspect_ratio'] = $finalData['aspect_ratio'];
        }
        if (isset($finalData['aspect_ratio_data'])) {
            $videoMetadata['aspect_ratio_data'] = $finalData['aspect_ratio_data'];
        }

        if (! empty($videoMetadata)) {
            $updates['video_metadata'] = $videoMetadata;
        }

        $result = self::update($sessionId, $updates);

        // Schedule cleanup after completion
        if ($result) {
            self::scheduleCleanup($sessionId);
        }

        return $result;
    }

    /**
     * Mark session as failed with error message.
     */
    public static function fail(string $sessionId, string $errorMessage): bool
    {
        $result = self::update($sessionId, [
            'status' => 'failed',
            'phase' => 'error',
            'error_message' => $errorMessage,
        ]);

        // Schedule cleanup after failure
        if ($result) {
            self::scheduleCleanup($sessionId);
        }

        return $result;
    }

    /**
     * Delete session from cache.
     */
    public static function delete(string $sessionId): bool
    {
        return Cache::forget(self::CACHE_PREFIX . $sessionId);
    }

    /**
     * Check if session exists and is valid.
     */
    public static function exists(string $sessionId): bool
    {
        return Cache::has(self::CACHE_PREFIX . $sessionId);
    }

    /**
     * Get session status.
     */
    public static function getStatus(string $sessionId): ?string
    {
        $sessionData = self::get($sessionId);

        return $sessionData['status'] ?? null;
    }

    /**
     * Clean up old sessions (can be called by a scheduled job).
     */
    public static function cleanup(): void
    {
        // Cache TTL handles cleanup automatically, but we could implement
        // additional cleanup logic here if needed
    }

    /**
     * Auto-cleanup session after completion or failure.
     * This extends session TTL briefly for final status checks.
     */
    public static function scheduleCleanup(string $sessionId, int $delayMinutes = 5): void
    {
        $sessionData = self::get($sessionId);
        if (! $sessionData) {
            return;
        }

        // Extend TTL briefly for final status checks, then auto-cleanup
        Cache::put(
            self::CACHE_PREFIX . $sessionId,
            $sessionData,
            now()->addMinutes($delayMinutes),
        );
    }
}
