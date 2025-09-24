<?php

declare(strict_types=1);

namespace App\Services\Directory\Strategies;

use Exception;
use Hdaklue\PathBuilder\Enums\SanitizationStrategy;
use Hdaklue\PathBuilder\PathBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class AvatarStorageStrategy extends BaseStorageStrategy
{
    public function store(UploadedFile $file): string
    {
        $secureFilename = PathBuilder::base('')
            ->addFile($file->getClientOriginalName(), SanitizationStrategy::SLUG)
            ->getFilename();

        $path = $file->storeAs($this->getDirectory(), $secureFilename, [
            'disk' => $this->getDisk(),
        ]);

        return $secureFilename;
    }

    public function fromPath(string $copyFrom, $toFileName)
    {
        $securePath = $this->buildSecurePath($toFileName);
        Storage::disk($this->getDisk())->move($copyFrom, $securePath);
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

    public function getSecureUrl(
        string $route,
        string $fileName,
        string $tenantId,
        string $type,
    ): string {
        throw new Exception('No need for Security');
    }

    public function getDisk()
    {
        return 'public';
    }

    // Override to add caching for avatar URLs
    public function getFileUrl(string $fileName): string
    {
        $securePath = $this->buildSecurePath($fileName);

        return Cache::rememberForever(md5($fileName), fn() => Storage::disk($this->getDisk())->url(
            $securePath,
        ));
    }
}
