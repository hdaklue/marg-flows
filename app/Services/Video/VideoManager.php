<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Services\Video\Services\VideoEditor;
use Illuminate\Contracts\Foundation\Application;

class VideoManager
{
    public function __construct(
        private readonly Application $app
    ) {}

    /**
     * Create a new video editor instance.
     */
    public function make(string $sourcePath, bool $isUrl = false, string $disk = 'local'): VideoEditor
    {
        return new VideoEditor($sourcePath, $isUrl, $disk);
    }

    /**
     * Create a video editor from a local file.
     */
    public function fromDisk(string $path, string $disk = 'local'): VideoEditor
    {
        return new VideoEditor($path, false, $disk);
    }

    /**
     * Create a video editor from a URL.
     */
    public function fromUrl(string $url, string $disk = 'local'): VideoEditor
    {
        return new VideoEditor($url, true, $disk);
    }

    /**
     * Create a video editor from the public disk.
     */
    public function fromPublic(string $path): VideoEditor
    {
        return new VideoEditor($path, false, 'public');
    }
}