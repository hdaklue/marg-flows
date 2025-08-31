<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

interface ChunksStorageStrategyContract extends StorageStrategyContract
{
    public function forSession(string $sessionId): self;

    public function deleteSession(): bool;
}