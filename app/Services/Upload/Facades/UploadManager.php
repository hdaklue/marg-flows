<?php

declare(strict_types=1);

namespace App\Services\Upload\Facades;

use App\Services\Upload\UploadManager as UploadManagerService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Upload\UploadManager simple()
 * @method static \App\Services\Upload\UploadManager chunked(\App\Services\Upload\DTOs\ChunkData $chunkData)
 * @method static \App\Services\Upload\UploadManager progressStrategy(\App\Services\Upload\Contracts\ProgressStrategyContract $progressStrategy)
 * @method static \App\Services\Upload\UploadManager forTenant(string $tenantId)
 * @method static \App\Services\Upload\UploadManager storeIn(string $directory)
 * @method static string upload(\Illuminate\Http\UploadedFile|\App\Services\Upload\DTOs\ChunkData $data)
 *
 * @see \App\Services\Upload\UploadManager
 */
final class UploadManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return UploadManagerService::class;
    }
}