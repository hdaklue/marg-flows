<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

interface DocumentStorageStrategyContract extends StorageStrategyContract
{
    public function forDocument(string $documentId): self;

    public function images(): self;

    public function videos(): self;

    public function documents(): self;
}