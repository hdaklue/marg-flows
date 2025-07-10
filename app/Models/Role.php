<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use BelongsToTenant,
        HasUlids;

    public function assignments(): HasMany
    {
        return $this->hasMany(ModelHasRole::class);
    }
}
