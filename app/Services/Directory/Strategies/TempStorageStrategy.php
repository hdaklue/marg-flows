<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use Exception;
use Hdaklue\PathBuilder\PathBuilder;
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
        $secureDirectory = PathBuilder::base($this->getDirectory())
            ->validate()
            ->toString();

        return Storage::deleteDirectory($secureDirectory);
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {
        throw new Exception('fix this ');
    }
}
