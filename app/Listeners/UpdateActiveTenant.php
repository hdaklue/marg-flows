<?php

declare(strict_types=1);

namespace App\Listeners;

use Exception;
use Filament\Events\TenantSet;

use function Illuminate\Log\log;

final class UpdateActiveTenant
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

        // Prevent duplicate switches - only switch if it's actually different
        if ($event->getUser()->getActiveTenantId() === $event->getTenant()->getKey()) {
            return; // Already on this tenant, no need to switch
        }

        try {
            // @phpstan-ignore-next-line
            $event->getUser()->switchActiveTenant($event->getTenant());
            ds("switching tenant to {$event->getTenant()->name}");
        } catch (Exception $e) {
            log()->error($e->getMessage());
            throw $e;
        }
    }
}
