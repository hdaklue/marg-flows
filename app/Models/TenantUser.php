<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    //

    protected $table = 'tenant_user';
}
