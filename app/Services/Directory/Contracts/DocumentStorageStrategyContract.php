<?php

declare(strict_types=1);

namespace App\Services\Directory\Contracts;

use App\Services\Directory\Strategies\ImageStorageStrategy;
use App\Services\Directory\Strategies\VideoStorageStrategy;

interface DocumentStorageStrategyContract extends StorageStrategyContract
{
    public function forDocument(string $documentId): self;

    public function images(): ImageStorageStrategy;

    public function videos(): VideoStorageStrategy;

    public function documents(): self;
}
