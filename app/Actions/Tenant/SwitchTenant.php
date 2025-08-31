<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Filament\Events\TenantSet;
use Lorisleiva\Actions\Concerns\AsAction;

final class SwitchTenant
{
    use AsAction;

    public function handle(User $user, $tenantId)
    {
        // Defense in depth - verify user can access tenant
        $tenant = Tenant::where('id', $tenantId)->firstOrFail();
        abort_unless($user->canAccessTenant($tenant), 404);

        // Dispatch the event for consistency and to trigger listeners
        event(new TenantSet($tenant, $user));

        return redirect()->to(filament()->getUrl(tenant: $tenant));
    }

    public function asController(User $user, $tenantId)
    {
        $this->handle($user, $tenantId);

        // For web requests, redirect after dispatching event
        $tenant = Tenant::where('id', $tenantId)->firstOrFail();

        return redirect()->to(filament()->getUrl(tenant: $tenant));
    }
}
