<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | EditorJS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for EditorJS integration including version management
    | and block validation settings.
    |
    */

    'version' => env('EDITORJS_VERSION', '2.28.2'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Block Types
    |--------------------------------------------------------------------------
    |
    | Define which block types are allowed in your EditorJS implementation.
    | This helps with validation and security.
    |
    */
    'allowed_blocks' => [
        'paragraph',
        'header',
        'list',
        'checklist',
        'quote',
        'code',
        'delimiter',
        'raw',
        'table',
        'image',
        'attaches',
        'embed',
        'linkTool',
        'marker',
        'inlineCode',
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define validation rules for specific block types if needed.
    |
    */
    'validation' => [
        'max_blocks' => 1000,
        'max_text_length' => 10000,
        'max_list_items' => 100,
    ],
];
