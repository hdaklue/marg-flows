<?php

declare(strict_types=1);

namespace App\Contracts\Tenant;

use App\Models\Tenant;
use Hdaklue\MargRbac\Models\RbacTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface BelongsToTenantContract
{
    public function getTenant(): RbacTenant;

    public function getTenantId(): string|int;
}
