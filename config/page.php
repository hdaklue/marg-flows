<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Page Caching
    |--------------------------------------------------------------------------
    |
    | This option controls whether the page management system should use
    | caching to improve performance. When enabled, pages and page lists
    | will be cached using content-based hashing for automatic invalidation.
    |
    */

    'should_cache' => env('PAGE_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings
    |--------------------------------------------------------------------------
    |
    | These options control how long different types of page data should
    | be cached. Individual pages use content-based caching, so they can
    | have longer TTL values since they auto-invalidate on changes.
    |
    */

    'cache_ttl' => [
        'page' => env('PAGE_CACHE_TTL_PAGE', 60 * 24), // 1 day for individual pages
        'list' => env('PAGE_CACHE_TTL_LIST', 60), // 1 hour for page lists
        'recent' => env('PAGE_CACHE_TTL_RECENT', 60), // 1 hour for recent pages
        'creator' => env('PAGE_CACHE_TTL_CREATOR', 60), // 1 hour for creator pages
        'user_pages' => env('PAGE_CACHE_TTL_USER_PAGES', 60 * 24), // 1 day for user-assigned pages
    ],

    /*
    |--------------------------------------------------------------------------
    | EditorJS Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for EditorJS content blocks when creating new pages.
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
    | Configuration for page search functionality.
    |
    */

    'search' => [
        'min_query_length' => 3,
        'max_results' => 50,
    ],
];
