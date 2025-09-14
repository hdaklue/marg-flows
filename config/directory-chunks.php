<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Chunks Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for chunk-based file upload storage operations
    | including session management and chunk processing.
    |
    */

    'storage' => [
        'disk' => env('DIRECTORY_CHUNKS_STORAGE_DISK', 'local'),
        'base_path' => env('DIRECTORY_CHUNKS_BASE_PATH', 'chunks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    |
    | Settings for chunk upload session management and TTL.
    |
    */

    'session_ttl' => env('DIRECTORY_CHUNKS_SESSION_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Chunk Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for individual chunk handling and limits.
    |
    */

    'processing' => [
        'max_chunk_size' => env('DIRECTORY_CHUNKS_MAX_CHUNK_SIZE', 10 * 1024 * 1024), // 10MB
        'min_chunk_size' => env('DIRECTORY_CHUNKS_MIN_CHUNK_SIZE', 1024), // 1KB
        'max_chunks_per_file' => env('DIRECTORY_CHUNKS_MAX_CHUNKS', 1000),
        'max_parallel_uploads' => env('DIRECTORY_CHUNKS_MAX_PARALLEL', 5),
        'verification_method' => env('DIRECTORY_CHUNKS_VERIFICATION', 'md5'), // md5, sha256, crc32
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific chunk storage isolation.
    |
    */

    'tenant_isolation' => [
        'enabled' => env('DIRECTORY_CHUNKS_TENANT_ISOLATION', true),
        'hash_strategy' => env('DIRECTORY_CHUNKS_HASH_STRATEGY', 'md5'), // md5, sha256
        'session_prefix' => env('DIRECTORY_CHUNKS_SESSION_PREFIX', 'chunk_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Policies
    |--------------------------------------------------------------------------
    |
    | Settings for automatic cleanup of expired chunks and sessions.
    |
    */

    'cleanup' => [
        'auto_cleanup' => env('DIRECTORY_CHUNKS_AUTO_CLEANUP', true),
        'cleanup_interval' => env('DIRECTORY_CHUNKS_CLEANUP_INTERVAL', 300), // 5 minutes
        'failed_chunks_retention' => env('DIRECTORY_CHUNKS_FAILED_RETENTION', 1800), // 30 minutes
        'completed_chunks_retention' => env('DIRECTORY_CHUNKS_COMPLETED_RETENTION', 300), // 5 minutes
        'orphaned_chunks_retention' => env('DIRECTORY_CHUNKS_ORPHANED_RETENTION', 600), // 10 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing chunk upload performance.
    |
    */

    'performance' => [
        'enable_compression' => env('DIRECTORY_CHUNKS_COMPRESSION', false),
        'compression_level' => env('DIRECTORY_CHUNKS_COMPRESSION_LEVEL', 6),
        'enable_deduplication' => env('DIRECTORY_CHUNKS_DEDUPLICATION', true),
        'memory_limit' => env('DIRECTORY_CHUNKS_MEMORY_LIMIT', '128M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings for chunk processing.
    |
    */

    'security' => [
        'validate_file_types' => env('DIRECTORY_CHUNKS_VALIDATE_TYPES', true),
        'scan_completed_files' => env('DIRECTORY_CHUNKS_SCAN_FILES', false),
        'max_filename_length' => env('DIRECTORY_CHUNKS_MAX_FILENAME', 255),
        'allowed_extensions' => [
            // Images
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff',
            // Videos
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp',
            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            // Archives
            'zip', 'rar', '7z', 'tar', 'gz',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for tracking upload progress and notifications.
    |
    */

    'progress' => [
        'track_progress' => env('DIRECTORY_CHUNKS_TRACK_PROGRESS', true),
        'broadcast_progress' => env('DIRECTORY_CHUNKS_BROADCAST_PROGRESS', false),
        'progress_update_interval' => env('DIRECTORY_CHUNKS_PROGRESS_INTERVAL', 1), // Every N chunks
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for chunk upload error handling and recovery.
    |
    */

    'error_handling' => [
        'max_retry_attempts' => env('DIRECTORY_CHUNKS_MAX_RETRIES', 3),
        'retry_delay' => env('DIRECTORY_CHUNKS_RETRY_DELAY', 1000), // milliseconds
        'log_errors' => env('DIRECTORY_CHUNKS_LOG_ERRORS', true),
        'quarantine_corrupted' => env('DIRECTORY_CHUNKS_QUARANTINE_CORRUPTED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for chunk upload operations.
    |
    */

    'rate_limiting' => [
        'enabled' => env('DIRECTORY_CHUNKS_RATE_LIMIT', true),
        'max_uploads_per_minute' => env('DIRECTORY_CHUNKS_RATE_LIMIT_UPLOADS', 100),
        'max_size_per_minute' => env('DIRECTORY_CHUNKS_RATE_LIMIT_SIZE', 100 * 1024 * 1024), // 100MB
    ],
];
