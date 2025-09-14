<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Document Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document-specific storage operations including
    | tenant isolation, file storage, and document-related settings.
    |
    */

    'storage' => [
        'disk' => env('DIRECTORY_DOCUMENT_STORAGE_DISK', 'public'),
        'base_path' => env('DIRECTORY_DOCUMENT_BASE_PATH', 'documents'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for caching document directory operations to improve performance.
    |
    */

    'cache_ttl' => env('DIRECTORY_DOCUMENT_CACHE_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant-specific document storage isolation.
    |
    */

    'tenant_isolation' => [
        'enabled' => env('DIRECTORY_DOCUMENT_TENANT_ISOLATION', true),
        'hash_strategy' => env('DIRECTORY_DOCUMENT_HASH_STRATEGY', 'md5'), // md5, sha256, etc.
        'directory_structure' => env('DIRECTORY_DOCUMENT_STRUCTURE', 'hashed'), // hashed, plain
    ],

    /*
    |--------------------------------------------------------------------------
    | File Operations
    |--------------------------------------------------------------------------
    |
    | Configuration for document file operations and limits.
    |
    */

    'operations' => [
        'max_file_size' => env('DIRECTORY_DOCUMENT_MAX_FILE_SIZE', 100 * 1024 * 1024), // 100MB
        'allowed_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ],
        'auto_cleanup' => env('DIRECTORY_DOCUMENT_AUTO_CLEANUP', true),
        'cleanup_after_days' => env('DIRECTORY_DOCUMENT_CLEANUP_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configurations for document storage.
    |
    */

    'security' => [
        'validate_mime_types' => env('DIRECTORY_DOCUMENT_VALIDATE_MIME', true),
        'scan_for_viruses' => env('DIRECTORY_DOCUMENT_VIRUS_SCAN', false),
        'encrypt_sensitive' => env('DIRECTORY_DOCUMENT_ENCRYPT_SENSITIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating URLs for document access.
    |
    */

    'urls' => [
        'default_expiry' => env('DIRECTORY_DOCUMENT_URL_EXPIRY', 1800), // 30 minutes
        'max_expiry' => env('DIRECTORY_DOCUMENT_URL_MAX_EXPIRY', 86400), // 24 hours
        'secure_by_default' => env('DIRECTORY_DOCUMENT_SECURE_URLS', true),
    ],
];
