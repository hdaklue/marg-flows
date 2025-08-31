<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Session Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the upload session manager,
    | including the default driver and driver-specific settings.
    |
    */

    'session' => [
        /*
        |--------------------------------------------------------------------------
        | Default Upload Session Driver
        |--------------------------------------------------------------------------
        |
        | This option controls the default upload session driver that will be used
        | when no specific driver is requested. The drivers available are:
        | "http", "websocket", "log"
        |
        */
        'default' => env('UPLOAD_SESSION_DRIVER', 'http'),

        /*
        |--------------------------------------------------------------------------
        | Upload Session Drivers
        |--------------------------------------------------------------------------
        |
        | Here you may configure the upload session drivers for your application.
        | Each driver can have its own configuration options.
        |
        */
        'drivers' => [
            'http' => [
                'cache_ttl' => 7200, // 2 hours in seconds
            ],
            
            'websocket' => [
                'channel_prefix' => 'upload-session.',
            ],
            
            'log' => [
                'channel' => 'single',
            ],
        ],
    ],
];