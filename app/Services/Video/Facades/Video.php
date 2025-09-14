<?php

declare(strict_types=1);

namespace App\Services\Video\Facades;

use App\Services\Video\Services\VideoEditor;
use App\Services\Video\VideoManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static VideoEditor make(string $sourcePath, bool $isUrl = false, string $disk = 'local')
 * @method static VideoEditor fromDisk(string $path, string $disk = 'local')
 * @method static VideoEditor fromUrl(string $url, string $disk = 'local')
 * @method static VideoEditor fromPublic(string $path)
 *
 * @see VideoManager
 */
final class Video extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'video';
    }
}
