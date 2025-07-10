<?php

declare(strict_types=1);

namespace App\Contracts\Tenant;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface BelongsToTenantContract
{
    public function getTenant(): Tenant;

    public function getTenantId(): string|int;
}
