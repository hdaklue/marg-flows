<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use App\Concerns\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, \App\Models\ModelHasRole> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\Tenant $tenant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role scopeByTenant(\App\Models\Tenant $tenant)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class Role extends Model
{
    use BelongsToTenant,
        HasUlids, LivesInOriginalDB;

    public $fillable = [
        'name',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ModelHasRole::class);
    }
}
