<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Chunk Size
    |--------------------------------------------------------------------------
    |
    | Default size for file chunks during upload. Larger chunks = fewer requests
    | but may hit server limits. Smaller chunks = more stable but slower.
    |
    */

    'default_chunk_size' => env('CHUNKED_UPLOAD_CHUNK_SIZE', 5 * 1024 * 1024), // 5MB

    /*
    |--------------------------------------------------------------------------
    | Maximum Parallel Uploads
    |--------------------------------------------------------------------------
    |
    | Maximum number of files that can be uploaded simultaneously.
    | Higher values increase throughput but consume more bandwidth.
    |
    */

    'max_parallel_uploads' => env('CHUNKED_UPLOAD_MAX_PARALLEL', 3),

    /*
    |--------------------------------------------------------------------------
    | Upload Routes
    |--------------------------------------------------------------------------
    |
    | Route names for chunked upload operations. These should match
    | the routes defined in your web.php or api.php files.
    |
    */

    'routes' => [
        'store' => env('CHUNKED_UPLOAD_STORE_ROUTE', 'chunked-upload.store'),
        'delete' => env('CHUNKED_UPLOAD_DELETE_ROUTE', 'chunked-upload.delete'),
        'cancel' => env('CHUNKED_UPLOAD_CANCEL_ROUTE', 'chunked-upload.cancel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunk Size Presets
    |--------------------------------------------------------------------------
    |
    | Common chunk sizes for different use cases:
    | - Small (1MB): Mobile/slow connections
    | - Medium (5MB): Default balanced setting
    | - Large (10MB): Fast connections/large files
    | - XLarge (25MB): Enterprise/internal networks
    |
    */

    'chunk_sizes' => [
        'small' => 1 * 1024 * 1024,    // 1MB
        'medium' => 5 * 1024 * 1024,   // 5MB (default)
        'large' => 10 * 1024 * 1024,   // 10MB
        'xlarge' => 25 * 1024 * 1024,  // 25MB
    ],

    /*
    |--------------------------------------------------------------------------
    | File Type Configurations
    |--------------------------------------------------------------------------
    |
    | Default configurations for different file types
    |
    */

    'file_types' => [
        'images' => [
            'accepted_types' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'image/bmp',
                'image/tiff',
            ],
            'max_size' => env('CHUNKED_UPLOAD_IMAGE_MAX_SIZE', 50 * 1024 * 1024), // 50MB
            'chunk_size' => env('CHUNKED_UPLOAD_IMAGE_CHUNK_SIZE', 2 * 1024 * 1024), // 2MB
            'previewable' => true,
        ],

        'videos' => [
            'accepted_types' => [
                'video/mp4',
                'video/mpeg',
                'video/quicktime',
                'video/x-msvideo', // .avi
                'video/x-ms-wmv',  // .wmv
                'video/webm',
                'video/ogg',
                'video/3gpp',
                'video/x-flv',
            ],
            'max_size' => env('CHUNKED_UPLOAD_VIDEO_MAX_SIZE', 500 * 1024 * 1024), // 500MB
            'chunk_size' => env('CHUNKED_UPLOAD_VIDEO_CHUNK_SIZE', 10 * 1024 * 1024), // 10MB
            'previewable' => true,
        ],

        'documents' => [
            'accepted_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
            ],
            'max_size' => env('CHUNKED_UPLOAD_DOCUMENT_MAX_SIZE', 100 * 1024 * 1024), // 100MB
            'chunk_size' => env('CHUNKED_UPLOAD_DOCUMENT_CHUNK_SIZE', 5 * 1024 * 1024), // 5MB
            'previewable' => false,
        ],

        'archives' => [
            'accepted_types' => [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
            ],
            'max_size' => env('CHUNKED_UPLOAD_ARCHIVE_MAX_SIZE', 1024 * 1024 * 1024), // 1GB
            'chunk_size' => env('CHUNKED_UPLOAD_ARCHIVE_CHUNK_SIZE', 25 * 1024 * 1024), // 25MB
            'previewable' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout Settings
    |--------------------------------------------------------------------------
    |
    | Timeout values for various upload operations (in seconds)
    |
    */

    'timeouts' => [
        'chunk_upload' => env('CHUNKED_UPLOAD_CHUNK_TIMEOUT', 120), // 2 minutes per chunk
        'total_upload' => env('CHUNKED_UPLOAD_TOTAL_TIMEOUT', 3600), // 1 hour total
        'cleanup_delay' => env('CHUNKED_UPLOAD_CLEANUP_DELAY', 300), // 5 minutes before cleanup
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Storage settings for chunked uploads
    |
    */

    'storage' => [
        'disk' => env('CHUNKED_UPLOAD_DISK', 'public'),
        'temp_directory' => env('CHUNKED_UPLOAD_TEMP_DIR', 'uploads/temp'),
        'final_directory' => env('CHUNKED_UPLOAD_FINAL_DIR', 'uploads'),
        'chunk_directory' => env('CHUNKED_UPLOAD_CHUNK_DIR', 'chunk-uploads'),
        'auto_cleanup' => env('CHUNKED_UPLOAD_AUTO_CLEANUP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configurations
    |
    */

    'security' => [
        'validate_mime_types' => env('CHUNKED_UPLOAD_VALIDATE_MIME', true),
        'scan_for_viruses' => env('CHUNKED_UPLOAD_VIRUS_SCAN', false),
        'allowed_extensions_only' => env('CHUNKED_UPLOAD_EXTENSIONS_ONLY', true),
        'max_filename_length' => env('CHUNKED_UPLOAD_MAX_FILENAME', 255),
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for upload progress tracking and notifications
    |
    */

    'progress' => [
        'update_frequency' => env('CHUNKED_UPLOAD_PROGRESS_FREQ', 1), // Update every N chunks
        'persist_progress' => env('CHUNKED_UPLOAD_PERSIST_PROGRESS', true),
        'broadcast_progress' => env('CHUNKED_UPLOAD_BROADCAST_PROGRESS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for upload endpoints
    |
    */

    'rate_limiting' => [
        'enabled' => env('CHUNKED_UPLOAD_RATE_LIMIT', true),
        'max_attempts' => env('CHUNKED_UPLOAD_MAX_ATTEMPTS', 100),
        'decay_minutes' => env('CHUNKED_UPLOAD_DECAY_MINUTES', 1),
    ],

];
