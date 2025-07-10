<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Role|null $role
 */
final class ModelHasRole extends MorphPivot
{
    public $timestamps = false;

    protected $fillable = [
        'model_type',
        'model_id',
        'roleable_type',
        'roleable_id',
        'role_id',
    ];

    /**
     * The model that has the role (e.g., User, Admin).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The entity this role is assigned to (e.g., Project, Team).
     */
    public function roleable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The assigned role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function getTable(): string
    {
        return config('role.table_names.model_has_roles');
    }

    public function getRole(): Role
    {
        return $this->role()->firstOrFail();
    }
}
