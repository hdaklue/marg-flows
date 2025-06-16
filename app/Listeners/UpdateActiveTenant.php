<?php

namespace App\Listeners;

use Filament\Events\TenantSet;

class UpdateActiveTenant
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(TenantSet $event): void
    {
        if (! $event->getUser()->canAccessTenant($event->getTenant())) {
            abort(404);
        }

        $event->getUser()->switchActiveTenant($event->getTenant());
    }
}
