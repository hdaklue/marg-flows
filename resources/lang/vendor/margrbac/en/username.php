<?php

return [
    'validation' => [
        'available' => 'The :attribute is already taken.',
        'format' => [
            'string' => 'The :attribute must be a string.',
            'min_length' => 'The :attribute must be at least :min characters.',
            'max_length' => 'The :attribute may not be greater than :max characters.',
            'invalid_chars' => 'The :attribute may only contain letters, numbers, and underscores.',
            'no_numbers' => 'The :attribute may not contain numbers.',
            'no_underscores' => 'The :attribute may not contain underscores.',
            'lowercase_only' => 'The :attribute must be lowercase.',
            'edge_underscores' => 'The :attribute may not start or end with an underscore.',
            'consecutive_underscores' => 'The :attribute may not contain consecutive underscores.',
        ],
        'reserved' => 'The :attribute is reserved and cannot be used.',
    ],

    'generation' => [
        'failed' => 'Unable to generate a unique username after :attempts attempts.',
        'success' => 'Username generated successfully.',
    ],

    'errors' => [
        'empty_source' => 'Cannot generate username from empty source.',
        'config_disabled' => 'Username generation is disabled.',
    ],
];
