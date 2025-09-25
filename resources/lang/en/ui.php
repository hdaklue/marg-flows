<?php

declare(strict_types=1);

return [
    'components' => [
        'language_switch' => [
            'label' => 'Language',
            'placeholder' => 'Select a language',
        ],

        'calendar' => [
            'today' => 'Today',
            'previous_month' => 'Previous Month',
            'next_month' => 'Next Month',
            'no_events' => 'No events',
        ],

        // Migrated from app.php file_upload
        'file_upload' => [
            'drag_drop' => 'Drag and drop files here or click to browse',
            'browse' => 'Browse Files',
            'max_size' => 'Maximum file size: :size',
            'supported_formats' => 'Supported formats: :formats',
            'uploading' => 'Uploading...',
            'upload_complete' => 'Uploaded Files',
            'upload_failed' => 'Upload failed',
            'file_too_large' => 'File is too large',
            'invalid_file_type' => 'Invalid file type',
            'video_upload' => 'Video Upload',
            'video_file' => 'Video File',
        ],

        'pagination' => [
            'previous' => 'Previous',
            'next' => 'Next',
            'showing' => 'Showing :from to :to of :total results',
            'per_page' => 'Per page',
        ],

        'search' => [
            'placeholder' => 'Search...',
            'no_results' => 'No results found',
            'searching' => 'Searching...',
            'clear' => 'Clear search',
        ],

        'modal' => [
            'close' => 'Close',
            'confirm' => 'Confirm',
            'cancel' => 'Cancel',
        ],

        'tooltip' => [
            'copy' => 'Copy',
            'copied' => 'Copied!',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'view' => 'View',
        ],

        'side_notes' => [
            'tooltip' => 'Sidenotes [Alt + S]',
            'title' => 'Side Notes',
            'subtitle' => 'Only you can see this notes',
            'placeholder' => 'write your note .. and hit Enter',
        ],

        'avatar_stack' => [
            'you' => 'You',
            'more_users_single' => ':count more user',
            'more_users_plural' => ':count more users',
            'manage_members' => 'Manage members',
        ],
    ],

    'panels' => [
        'admin' => [
            'title' => 'Admin Panel',
            'description' => 'Administration and system management',
        ],
        'portal' => [
            'title' => 'Portal',
            'description' => 'Main application portal',
        ],
    ],

    'themes' => [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],
];
