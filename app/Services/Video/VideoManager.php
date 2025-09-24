<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Services\Video\Services\ResolutionManager;
use App\Services\Video\Services\VideoEditor;
use Illuminate\Contracts\Foundation\Application;

final class VideoManager
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * Create a video object.
     */
    public function make(string $sourcePath, bool $isUrl = false, string $disk = 'local'): Video
    {
        return new Video($sourcePath, $isUrl, $disk);
    }

    /**
     * Create a video object from a local file.
     */
    public function fromDisk(string $path, string $disk = 'local'): Video
    {
        return new Video($path, false, $disk);
    }

    /**
     * Create a video object from a URL.
     */
    public function fromUrl(string $url, string $disk = 'local'): Video
    {
        return new Video($url, true, $disk);
    }

    /**
     * Create a video object from the public disk.
     */
    public function fromPublic(string $path): Video
    {
        return new Video($path, false, 'public');
    }

    /**
     * Create a video editor from a video object.
     */
    public function editor(Video $video): VideoEditor
    {
        return new VideoEditor($video);
    }

    /**
     * Create a resolution manager from a video object.
     */
    public function resolutions(Video $video): ResolutionManager
    {
        return new ResolutionManager($video);
    }

    /**
     * Create a video editor from a path (convenience method).
     */
    public function edit(string $path, string $disk = 'local'): VideoEditor
    {
        $video = $this->fromDisk($path, $disk);

        return $this->editor($video);
    }

    /**
     * Create a resolution manager from a path (convenience method).
     */
    public function convert(string $path, string $disk = 'local'): ResolutionManager
    {
        $video = $this->fromDisk($path, $disk);

        return $this->resolutions($video);
    }
}
