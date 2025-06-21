<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Strict Email Matching
    |--------------------------------------------------------------------------
    |
    | When true, the authenticated user's email must match the invitation email.
    | When false, any authenticated user can accept any valid invitation.
    |
    */
    'strict_email_matching' => env('INVITATION_STRICT_EMAIL', true),

    /*
    |--------------------------------------------------------------------------
    | Invitation Expiry
    |--------------------------------------------------------------------------
    |
    | Default number of days before invitations expire
    |
    */
    'expires_after_days' => env('INVITATION_EXPIRES_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Log Email Mismatches
    |--------------------------------------------------------------------------
    |
    | Whether to log when invitations are accepted by different emails
    | (only relevant when strict_email_matching is false)
    |
    */
    'log_email_mismatches' => env('INVITATION_LOG_MISMATCHES', false),
];
