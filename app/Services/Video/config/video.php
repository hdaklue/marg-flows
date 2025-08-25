<?php

declare(strict_types=1);

use App\Services\Video\Enums\NamingPattern;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | The default disk to use for video operations when none is specified.
    |
    */
    'default_disk' => env('VIDEO_DEFAULT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Naming Pattern
    |--------------------------------------------------------------------------
    |
    | The default naming pattern to use when generating output filenames.
    |
    */
    'default_naming_pattern' => NamingPattern::Quality,

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | Global settings for video processing operations.
    |
    */
    'processing' => [
        // Default frame rate for video processing
        'default_frame_rate' => 30,

        // Default bitrate multiplier for quality calculations
        'bitrate_multiplier' => 1.0,

        // Whether to allow scale-up operations by default
        'allow_scale_up' => false,

        // Maximum processing timeout in seconds
        'timeout' => env('VIDEO_PROCESSING_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Presets
    |--------------------------------------------------------------------------
    |
    | Predefined quality settings for common video formats.
    |
    */
    'quality_presets' => [
        '144p' => [
            'width' => 256,
            'height' => 144,
            'bitrate' => 200,
            'fps' => 15,
        ],
        '240p' => [
            'width' => 426,
            'height' => 240,
            'bitrate' => 400,
            'fps' => 24,
        ],
        '360p' => [
            'width' => 640,
            'height' => 360,
            'bitrate' => 800,
            'fps' => 24,
        ],
        '480p' => [
            'width' => 854,
            'height' => 480,
            'bitrate' => 1200,
            'fps' => 30,
        ],
        '720p' => [
            'width' => 1280,
            'height' => 720,
            'bitrate' => 2500,
            'fps' => 30,
        ],
        '1080p' => [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => 5000,
            'fps' => 30,
        ],
        '1440p' => [
            'width' => 2560,
            'height' => 1440,
            'bitrate' => 6000,
            'fps' => 30,
        ],
        '4K' => [
            'width' => 3840,
            'height' => 2160,
            'bitrate' => 8000,
            'fps' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Default paths for video storage.
    |
    */
    'paths' => [
        'uploads' => 'videos/uploads',
        'processed' => 'videos/processed',
        'temp' => 'videos/temp',
        'thumbnails' => 'videos/thumbnails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Formats
    |--------------------------------------------------------------------------
    |
    | List of supported input and output video formats.
    |
    */
    'formats' => [
        'input' => ['mp4', 'avi', 'mov', 'mkv', 'webm', 'flv'],
        'output' => ['mp4', 'webm', 'mov'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermark Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for video watermarks.
    |
    */
    'watermark' => [
        'default_position' => 'bottom-right',
        'default_opacity' => 0.8,
        'margin_x' => 25,
        'margin_y' => 25,
    ],
];