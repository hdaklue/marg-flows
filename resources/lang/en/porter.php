<?php

declare(strict_types=1);

return [
    'roles' => [
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full administrative access to assigned entities',
        ],
        'manager' => [
            'label' => 'Manager',
            'description' => 'Manage content and assign roles within entities',
        ],
        'editor' => [
            'label' => 'Editor',
            'description' => 'Create, edit, and delete content',
        ],
        'contributor' => [
            'label' => 'Contributor',
            'description' => 'Create and edit own content',
        ],
        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only access to content',
        ],
        'guest' => [
            'label' => 'Guest',
            'description' => 'Limited access to public content',
        ],
    ],
];
