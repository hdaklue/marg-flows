<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Services\Video\Services\VideoEditor;
use InvalidArgumentException;

class Video
{
    /**
     * Load a video from file path.
     */
    public static function load(string $path): VideoEditor
    {
        throw_if(!file_exists($path), new InvalidArgumentException("Video file not found: {$path}"));
        
        return new VideoEditor($path);
    }

    /**
     * Load a video from URL.
     */
    public static function loadFromUrl(string $url): VideoEditor
    {
        throw_if(!filter_var($url, FILTER_VALIDATE_URL), new InvalidArgumentException("Invalid URL: {$url}"));
        
        return new VideoEditor($url, true);
    }

    /**
     * Create a video editor instance from an existing video path.
     */
    public static function make(string $path): VideoEditor
    {
        return self::load($path);
    }
}