<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 
 *
 * @property int $id
 * @property string $user_id
 * @property string $tenant_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser whereUserId($value)
 * @mixin \Eloquent
 */
class TenantUser extends Pivot
{
    //

    protected $table = 'tenant_user';
}
