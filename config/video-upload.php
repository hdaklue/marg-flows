<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Video Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the EditorJS video upload functionality
    |
    */

    'storage' => [
        // Directory for storing video chunks during upload
        'chunk_directory' => 'video-chunk-uploads',

        // Final storage directory for uploaded videos
        'video_directory' => 'documents/videos',

        // Directory for video thumbnails
        'thumbnail_directory' => 'documents/video-thumbnails',

        // Storage disk to use
        'disk' => 'public',
    ],

    'validation' => [
        // Maximum file size in KB (500MB = 512000 KB)
        'max_file_size' => 512000, // 500MB

        // Allowed video MIME types (Video.js compatible only)
        'allowed_mimes' => [
            'mp4', 'webm', 'ogg',
        ],

        // Allowed video MIME types (full)
        'allowed_mimetypes' => [
            'video/mp4',
            'video/webm',
            'video/ogg',
        ],
    ],

    'chunks' => [
        // Default chunk size in bytes (10MB)
        'default_size' => 10 * 1024 * 1024,

        // Maximum number of chunks allowed
        'max_chunks' => 100,

        // Chunk cleanup time in hours (old chunks will be cleaned up)
        'cleanup_hours' => 24,
    ],

    'processing' => [
        // Whether to generate video thumbnails
        'generate_thumbnails' => true,

        // Thumbnail extraction time (seconds from start, or percentage if < 1)
        'thumbnail_time' => 1.0,

        // Whether to extract video metadata using FFmpeg
        'extract_metadata' => true,

        // Default aspect ratio fallback
        'default_aspect_ratio' => '16:9',
    ],

    'conversion' => [
        // Server-side conversion disabled - only accept Video.js compatible formats
        'server_side_enabled' => false,

        // No conversion needed - only Video.js compatible formats allowed
        'convert_formats' => [],

        // FFmpeg settings (not used)
        'ffmpeg' => [
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
            'preset' => 'fast',
            'crf' => 23,
            'audio_bitrate' => '128k',
        ],
    ],
];
