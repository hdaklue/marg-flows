<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Recency Throttle Minutes
    |--------------------------------------------------------------------------
    |
    | The number of minutes to throttle recent interactions. This prevents
    | excessive database writes when users rapidly interact with the same
    | item. For project management apps, 15-30 minutes is recommended.
    |
    */
    'throttle_minutes' => env('RECENCY_THROTTLE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Default Limit
    |--------------------------------------------------------------------------
    |
    | The default number of recent items to retrieve when no limit is specified.
    |
    */
    'default_limit' => env('RECENCY_DEFAULT_LIMIT', 10),
];