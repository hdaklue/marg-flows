<?php

declare(strict_types=1);

namespace App\Services\Upload\Contracts;

use App\Services\Upload\DTOs\ProgressData;

interface ProgressStrategyContract
{
    /**
     * Initialize progress tracking for a session.
     */
    public function init(string $sessionId, array $metadata): void;

    /**
     * Update progress for a session.
     */
    public function updateProgress(string $sessionId, ProgressData $progress): void;

    /**
     * Mark session as completed.
     */
    public function complete(string $sessionId, mixed $result): void;

    /**
     * Mark session as failed.
     */
    public function error(string $sessionId, string $error): void;

    /**
     * Get current progress for a session.
     */
    public function getProgress(string $sessionId): null|ProgressData;

    /**
     * Clean up session data.
     */
    public function cleanup(string $sessionId): void;
}
