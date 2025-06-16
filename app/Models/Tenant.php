<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    /** @use HasUlids */
    use HasFactory, HasUlids;

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class);
    }
}
