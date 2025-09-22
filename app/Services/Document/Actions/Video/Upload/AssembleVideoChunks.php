<?php

declare(strict_types=1);

namespace App\Services\Document\Actions\Video\Upload;

use App\Models\Document;
use App\Services\Document\Sessions\VideoUploadSessionManager;
use App\Services\Upload\UploadSessionService;
use Exception;
use Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class AssembleVideoChunks
{
    use AsAction;

    public int $tries = 3;

    public int $timeout = 10 * 60; // 10 minutes for assembly only

    public static function getJobQueue(): string
    {
        return 'document-video-upload';
    }

    /**
     * Assemble uploaded video chunks into a single local file.
     */
    public function handle(
        UploadSessionService $sessionManager,
        string $sessionId,
        ?string $fileName,
        int $totalChunks,
        Document $document,
        ?string $videoSessionId = null,
    ): string {
        try {
            Log::info('Starting video chunk assembly', [
                'sessionId' => $sessionId,
                'videoSessionId' => $videoSessionId,
                'fileName' => $fileName,
                'totalChunks' => $totalChunks,
                'documentId' => $document->id,
            ]);

            // Wait a moment to ensure all chunks are fully written to disk
            sleep(2);

            // Assemble all chunks into final file (stored locally)
            $localFinalPath = $sessionManager->assembleFile(
                $sessionId,
                $fileName ?? 'video.mp4',
                $totalChunks,
            );

            // Clean up chunk files after successful assembly
            $sessionManager->cleanupSession($sessionId);

            Log::info('Video chunk assembly completed', [
                'sessionId' => $sessionId,
                'localPath' => $localFinalPath,
                'documentId' => $document->id,
            ]);

            // Update session status
            if ($videoSessionId) {
                VideoUploadSessionManager::update($videoSessionId, [
                    'phase' => 'conversion',
                    'status' => 'processing',
                ]);
            }

            // Chain to conversion action with a small delay
            ConvertVideoAction::dispatch(
                $localFinalPath,
                $document,
                $videoSessionId,
            )->delay(now()->addSeconds(3))->onQueue('document-video-upload');

            return $localFinalPath;
        } catch (Exception $e) {
            Log::error('Video chunk assembly failed', [
                'sessionId' => $sessionId,
                'videoSessionId' => $videoSessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'documentId' => $document->id,
                'fileName' => $fileName,
                'totalChunks' => $totalChunks,
            ]);

            // Mark session as failed
            if ($videoSessionId) {
                VideoUploadSessionManager::fail(
                    $videoSessionId,
                    'Failed to assemble video chunks: ' . $e->getMessage(),
                );
            }

            throw $e;
        }
    }
}
