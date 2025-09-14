<?php

declare(strict_types=1);

namespace App\Contracts\Tenant;

use Hdaklue\MargRbac\Models\RbacTenant;

interface BelongsToTenantContract
{
    public function getTenant(): RbacTenant;

    public function getTenantId(): string|int;
}
