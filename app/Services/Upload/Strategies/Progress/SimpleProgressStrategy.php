<?php

declare(strict_types=1);

namespace App\Services\Upload\Strategies\Progress;

use App\Services\Upload\Contracts\ProgressStrategyContract;
use App\Services\Upload\DTOs\ProgressData;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;

final class SimpleProgressStrategy implements ProgressStrategyContract
{
    private const string REDIS_PREFIX = 'upload_progress:';

    private const int TTL = 3600; // 1 hour

    public function init(string $sessionId, array $metadata): void
    {
        $this->validateSessionId($sessionId);

        $initialProgress = new ProgressData(
            completedChunks: 0,
            totalChunks: $metadata['totalChunks'] ?? 1,
            bytesUploaded: 0,
            totalBytes: $metadata['totalBytes'] ?? 0,
            percentage: 0.0,
            status: 'initialized',
        );

        $this->storeProgress($sessionId, $initialProgress);
    }

    public function updateProgress(string $sessionId, ProgressData $progress): void
    {
        $this->validateSessionId($sessionId);
        $this->storeProgress($sessionId, $progress);
    }

    public function complete(string $sessionId, mixed $result): void
    {
        $this->validateSessionId($sessionId);

        $currentProgress = $this->getProgress($sessionId);
        throw_unless(
            $currentProgress,
            new InvalidArgumentException("Progress session '{$sessionId}' not found."),
        );

        $completedProgress = new ProgressData(
            completedChunks: $currentProgress->totalChunks,
            totalChunks: $currentProgress->totalChunks,
            bytesUploaded: $currentProgress->totalBytes,
            totalBytes: $currentProgress->totalBytes,
            percentage: 100.0,
            status: 'completed',
        );

        $this->storeProgress($sessionId, $completedProgress);
    }

    public function error(string $sessionId, string $error): void
    {
        $this->validateSessionId($sessionId);

        $currentProgress = $this->getProgress($sessionId);
        if (!$currentProgress) {
            // Create error progress if session doesn't exist
            $errorProgress = new ProgressData(
                completedChunks: 0,
                totalChunks: 1,
                bytesUploaded: 0,
                totalBytes: 0,
                percentage: 0.0,
                status: 'error',
                error: $error,
            );
        } else {
            $errorProgress = new ProgressData(
                completedChunks: $currentProgress->completedChunks,
                totalChunks: $currentProgress->totalChunks,
                bytesUploaded: $currentProgress->bytesUploaded,
                totalBytes: $currentProgress->totalBytes,
                percentage: $currentProgress->percentage,
                status: 'error',
                error: $error,
            );
        }

        $this->storeProgress($sessionId, $errorProgress);
    }

    public function getProgress(string $sessionId): null|ProgressData
    {
        $this->validateSessionId($sessionId);

        $data = Redis::get($this->getRedisKey($sessionId));
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (!$decoded) {
            return null;
        }

        return ProgressData::fromArray($decoded);
    }

    public function cleanup(string $sessionId): void
    {
        $this->validateSessionId($sessionId);
        Redis::del($this->getRedisKey($sessionId));
    }

    private function storeProgress(string $sessionId, ProgressData $progress): void
    {
        Redis::setex($this->getRedisKey($sessionId), self::TTL, json_encode($progress->toArray()));
    }

    private function getRedisKey(string $sessionId): string
    {
        return self::REDIS_PREFIX . $sessionId;
    }

    private function validateSessionId(string $sessionId): void
    {
        throw_if(empty($sessionId), new InvalidArgumentException('Session ID cannot be empty.'));
    }
}
