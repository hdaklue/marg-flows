<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\StorageStrategyContract;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class TempStorageStrategy implements StorageStrategyContract
{
    public function store(UploadedFile $file): string
    {
        return 'By Pass';
    }

    public function getDirectory(): string
    {
        return 'system_temp';
    }

    public function delete(string $fileName): bool
    {
        return Storage::delete($this->getDirectory() . "/{$fileName}");
    }

    public function deleteAll(): bool
    {
        return Storage::deleteDirectory($this->getDirectory());
    }

    public function get(string $fileName): ?string
    {
        return Storage::get($this->getDirectory() . "/{$fileName}");
    }

    public function getPath(string $fileName): ?string
    {
        $fullPath = $this->getDirectory() . "/{$fileName}";

        if (Storage::getDefaultDriver() === 'local') {
            return Storage::path($fullPath);
        }

        return $fullPath;
    }

    public function getFileUrl(string $fileName): string
    {
        return Storage::url($this->getDirectory() . "/{$fileName}");
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {
        throw new Exception('fix this ');
    }
}
