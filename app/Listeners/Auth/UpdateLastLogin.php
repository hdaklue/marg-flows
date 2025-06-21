<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use Illuminate\Auth\Events\Login;

class UpdateLastLogin
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $userAgent = request()->userAgent();
        $ip = request()->ip();

        $event->user->updateLastLogin($userAgent, $ip);
    }
}
