<?php

declare(strict_types=1);
use App\Services\Document\Templates\General;

return [
    /*
    |--------------------------------------------------------------------------
    | DOCUMENT Caching
    |--------------------------------------------------------------------------
    |
    | This option controls whether the DOCUMENT management system should use
    | caching to improve performance. When enabled, DOCUMENTs and DOCUMENT lists
    | will be cached using content-based hashing for automatic invalidation.
    |
    */

    'should_cache' => env('DOCUMENT_CACHE_ENABLED', true),

    'templates' => [
        'general' => General::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings
    |--------------------------------------------------------------------------
    |
    | These options control how long different types of DOCUMENT data should
    | be cached. Individual DOCUMENTs use content-based caching, so they can
    | have longer TTL values since they auto-invalidate on changes.
    |
    */

    'cache_ttl' => [
        'document' => env('DOCUMENT_CACHE_TTL_DOCUMENT', 60 * 24), // 1 day for individual DOCUMENTs
        'list' => env('DOCUMENT_CACHE_TTL_LIST', 60), // 1 hour for DOCUMENT lists
        'recent' => env('DOCUMENT_CACHE_TTL_RECENT', 60), // 1 hour for recent DOCUMENTs
        'creator' => env('DOCUMENT_CACHE_TTL_CREATOR', 60), // 1 hour for creator DOCUMENTs
        'user_DOCUMENTs' => env('DOCUMENT_CACHE_TTL_USER_DOCUMENTS', 60 * 24), // 1 day for user-assigned DOCUMENTs
    ],

    /*
    |--------------------------------------------------------------------------
    | EditorJS Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for EditorJS content blocks when creating new DOCUMENTs.
    |
    */

    'editorjs' => [
        'version' => '2.28.2',
        'default_blocks' => [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Start writing...',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for DOCUMENT search functionality.
    |
    */

    'search' => [
        'min_query_length' => 3,
        'max_results' => 50,
    ],
];
