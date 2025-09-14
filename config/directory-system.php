<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for system-wide storage operations including
    | avatars, temporary files, and system-level file management.
    |
    */

    'storage' => [
        'disk' => env('DIRECTORY_SYSTEM_STORAGE_DISK', 'public'),
        'base_path' => env('DIRECTORY_SYSTEM_BASE_PATH', 'system'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Avatar Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specific to user avatar storage and management.
    |
    */

    'avatars' => [
        'directory' => env('DIRECTORY_SYSTEM_AVATARS_DIR', 'avatars'),
        'max_size' => env('DIRECTORY_SYSTEM_AVATARS_MAX_SIZE', 5 * 1024 * 1024), // 5MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'dimensions' => [
            'max_width' => env('DIRECTORY_SYSTEM_AVATARS_MAX_WIDTH', 1024),
            'max_height' => env('DIRECTORY_SYSTEM_AVATARS_MAX_HEIGHT', 1024),
            'thumbnail_size' => env('DIRECTORY_SYSTEM_AVATARS_THUMB_SIZE', 150),
        ],
        'auto_resize' => env('DIRECTORY_SYSTEM_AVATARS_AUTO_RESIZE', true),
        'generate_thumbnails' => env('DIRECTORY_SYSTEM_AVATARS_THUMBNAILS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary Files Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for temporary file storage and cleanup policies.
    |
    */

    'temp' => [
        'directory' => env('DIRECTORY_SYSTEM_TEMP_DIR', 'temp'),
        'cleanup_interval' => env('DIRECTORY_SYSTEM_TEMP_CLEANUP_INTERVAL', 3600), // 1 hour
        'max_age' => env('DIRECTORY_SYSTEM_TEMP_MAX_AGE', 86400), // 24 hours
        'max_size' => env('DIRECTORY_SYSTEM_TEMP_MAX_SIZE', 50 * 1024 * 1024), // 50MB
        'auto_cleanup' => env('DIRECTORY_SYSTEM_TEMP_AUTO_CLEANUP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for caching system directory operations.
    |
    */

    'cache_ttl' => env('DIRECTORY_SYSTEM_CACHE_TTL', 1800), // 30 minutes

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configurations for system storage.
    |
    */

    'security' => [
        'validate_mime_types' => env('DIRECTORY_SYSTEM_VALIDATE_MIME', true),
        'scan_uploads' => env('DIRECTORY_SYSTEM_SCAN_UPLOADS', false),
        'quarantine_suspicious' => env('DIRECTORY_SYSTEM_QUARANTINE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Logs & Analytics
    |--------------------------------------------------------------------------
    |
    | Configuration for system file operations logging.
    |
    */

    'logging' => [
        'log_operations' => env('DIRECTORY_SYSTEM_LOG_OPS', true),
        'log_level' => env('DIRECTORY_SYSTEM_LOG_LEVEL', 'info'),
        'retention_days' => env('DIRECTORY_SYSTEM_LOG_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Access
    |--------------------------------------------------------------------------
    |
    | Settings for public file access and URL generation.
    |
    */

    'public_access' => [
        'avatars_public' => env('DIRECTORY_SYSTEM_AVATARS_PUBLIC', true),
        'default_expiry' => env('DIRECTORY_SYSTEM_URL_EXPIRY', 3600), // 1 hour
        'cache_control' => env('DIRECTORY_SYSTEM_CACHE_CONTROL', 'public, max-age=3600'),
    ],
];
