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

    /**
     * Get secure URL for accessing a file with authentication.
     *
     * @param  string  $route  The route name
     * @param  string  $fileName  The filename to get secure URL for
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $type  The file type (documents, videos, etc.)
     * @return string Secure URL requiring authentication
     */
    public function getSecureUrl(
        string $route,
        string $fileName,
        string $tenantId,
        string $type,
    ): string {
        return route($route, [
            'tenant' => $tenantId,
            'type' => $type,
            'filename' => $fileName,
        ]);
    }
}
