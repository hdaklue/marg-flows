<?php

declare(strict_types=1);

namespace App\Services\Upload\Strategies\Progress;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\DTOs\ProgressData;
use Log;

final class LogProgressStrategy implements ProgressStrategyContract
{
    public function init(string $sessionId, array $metadata): void
    {
        Log::info('Upload session initialized', [
            'sessionId' => $sessionId,
            'metadata' => $metadata,
        ]);
    }

    public function updateProgress(
        string $sessionId,
        ProgressData $progress,
    ): void {
        Log::info('Upload progress updated', [
            'sessionId' => $sessionId,
            'progress' => $progress->toArray(),
        ]);
    }

    public function complete(string $sessionId, mixed $result): void
    {
        Log::info('Upload session completed', [
            'sessionId' => $sessionId,
            'result' => $result,
        ]);
    }

    public function error(string $sessionId, string $message): void
    {
        Log::error('Upload session failed', [
            'sessionId' => $sessionId,
            'error' => $message,
        ]);
    }

    public function getProgress(string $sessionId): ?ProgressData
    {
        // Log strategy doesn't store progress data for retrieval
        return null;
    }

    public function cleanup(string $sessionId): void
    {
        Log::info('Upload session cleaned up', [
            'sessionId' => $sessionId,
        ]);
    }
}
