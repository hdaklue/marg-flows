<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use App\Services\Directory\Contracts\StorageStrategyContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class AvatarStorageStrategy implements StorageStrategyContract
{
    public function store(UploadedFile $file): string
    {
        $path = $file->storeAs($this->getDirectory(), $file->getClientOriginalName());

        return $file->getClientOriginalName();

    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $fileName): bool
    {
        return Storage::delete($this->getDirectory() . "/{$fileName}");
    }

    /**
     * {@inheritDoc}
     */
    public function getDirectory(): string
    {
        return 'system_avatars';
    }

    public function getPath(string $fileName): ?string
    {
        $fullPath = $this->getDirectory() . "/{$fileName}";

        // Only return path for local disks
        if (Storage::getDefaultDriver() === 'local') {
            return Storage::path($fullPath);
        }

        // For cloud storage, return the storage path (not local file path)
        return $fullPath;
    }

    public function get(string $fileName): ?string
    {
        return Storage::get($this->getDirectory() . "/{$fileName}");
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {
        return 'url';
    }

    public function getFileUrl(string $fileName): string
    {
        return Cache::rememberForever(md5($fileName), fn () => Storage::url($this->getDirectory() . "/{$fileName}"));

    }
}
