<?php

declare(strict_types=1);

namespace App\Concerns\Tenant;

use App\Models\Tenant;
use Exception;

trait HasActiveTenant
{
    public function activeTenant(): ?Tenant
    {
        return Tenant::where('id', $this->active_tenant_id)->first();
    }

    public function switchActiveTenant(Tenant $tenant): ?self
    {
        if ($this->isAssignedTo($tenant)) {
            $this->active_tenant_id = $tenant->getKey();
            $this->save();

            return $this;
        }
        throw new Exception('Teanant Cannot be assgined to User as It does not Has Role On');
    }

    public function clearActiveTenant()
    {
        $this->update(['active_tenant_id' => null]);
    }

    public function getActiveTenantId(): ?string
    {
        return $this->active_tenant_id;
    }
}
