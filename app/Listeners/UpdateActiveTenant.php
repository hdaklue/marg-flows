<?php

namespace App\Listeners;

use Filament\Events\TenantSet;

use function Illuminate\Log\log;

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

        try {
            $event->getUser()->switchActiveTenant($event->getTenant());
            setPermissionsTeamId($event->getTenant()->id);
        } catch (\Exception $e) {
            log()->error($e->getMessage());
            throw $e;
        }

    }
}
