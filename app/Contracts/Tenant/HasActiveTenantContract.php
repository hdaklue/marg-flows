<?php

declare(strict_types=1);

namespace App\Contracts\Tenant;

use App\Models\Tenant;

interface HasActiveTenantContract
{
    public function activeTenant(): ?Tenant;

    public function switchActiveTenant(Tenant $tenant);

    public function clearActiveTenant();

    public function getActiveTenantId(): ?string;
}
