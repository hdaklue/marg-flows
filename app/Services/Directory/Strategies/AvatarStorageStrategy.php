<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class AvatarStorageStrategy extends BaseStorageStrategy
{
    public function store(UploadedFile $file): string
    {
        $path = $file->storeAs(
            $this->getDirectory(),
            $file->getClientOriginalName(),
        );

        return $file->getClientOriginalName();
    }

    public function fromPath(string $copyFrom, $toFileName)
    {
        Storage::move($copyFrom, $this->getDirectory() . "/{$toFileName}");
    }

    public function getDirectory(): string
    {
        return 'system_avatars';
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {
        return 'url';
    }

    // Override to add caching for avatar URLs
    public function getFileUrl(string $fileName): string
    {
        return Cache::rememberForever(md5($fileName), fn () => Storage::url(
            $this->getDirectory() . "/{$fileName}",
        ));
    }
}
