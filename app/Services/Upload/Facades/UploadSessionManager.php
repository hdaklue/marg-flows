<?php

declare(strict_types=1);

namespace App\Services\Upload\Facades;

use App\Services\Upload\UploadSessionManager as UploadSessionManagerService;
use App\Services\Upload\UploadSessionService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static UploadSessionService driver(string $driver = null)
 * @method static UploadSessionService forTenant(string $tenantId)
 * @method static UploadSessionService setChunkDirectory(string $directory)
 * @method static UploadSessionService storeIn(string $directory)
 * @method static string initSession(string $fileName, int $totalChunks, int $totalSize = null)
 * @method static void storeChunk(string $sessionId, \Illuminate\Http\UploadedFile $chunk, int $chunkIndex)
 * @method static string processChunks(\App\Services\Upload\DTOs\ChunkData $chunkData)
 * @method static bool isComplete(string $sessionId, int $totalChunks)
 * @method static string assembleFile(string $sessionId, string $fileName, int $totalChunks)
 * @method static void cleanupSession(string $sessionId)
 * @method static \App\Services\Upload\DTOs\ProgressData|null getProgress(string $sessionId)
 *
 * @see \App\Services\Upload\UploadSessionManager
 */
final class UploadSessionManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return UploadSessionManagerService::class;
    }
}
