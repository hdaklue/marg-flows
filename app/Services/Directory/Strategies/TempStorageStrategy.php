<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class TempStorageStrategy extends BaseStorageStrategy
{
    public function store(UploadedFile $file): string
    {
        return 'By Pass';
    }

    public function getDirectory(): string
    {
        return 'system_temp';
    }

    public function deleteAll(): bool
    {
        return Storage::deleteDirectory($this->getDirectory());
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {
        throw new Exception('fix this ');
    }
}
