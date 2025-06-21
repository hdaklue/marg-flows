<?php

declare(strict_types=1);

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
    // As it's type Hinted TenantSet it's auto-discovered
    public function handle(TenantSet $event): void
    {
        abort_unless($event->getUser()->canAccessTenant($event->getTenant()), 404);

        try {
            $event->getUser()->switchActiveTenant($event->getTenant());
            setPermissionsTeamId($event->getTenant()->id);
        } catch (\Exception $e) {
            log()->error($e->getMessage());
            throw $e;
        }

    }
}
