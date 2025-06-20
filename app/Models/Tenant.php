<?php

namespace App\Models;

use App\Concerns\Roles\RoleableEntity;
use App\Contracts\Roles\HasParticipants;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model implements HasParticipants
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    /** @use HasUlids */
    use HasFactory, HasUlids, RoleableEntity;

    protected $fillable = ['name'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function systemRoles(): HasMany
    {
        return $this->hasMany(config('permission.models.role'), config('permission.column_names.team_foreign_key'));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class);
    }
}
