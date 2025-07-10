<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role\RoleEnum;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property int $id
 * @property string $model_type
 * @property string $model_id
 * @property string $roleable_type
 * @property string $roleable_id
 * @property string $role_id
 * @property-read Model|\Eloquent $model
 * @property-read \App\Models\Role $role
 * @property-read Model|\Eloquent $roleable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleableType($value)
 * @mixin Eloquent
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
     * @return BelongsTo<Role, $this>
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

    // public function getModelName(): string
    // {
    //     return "{$this->model->name} - " . RoleEnum::from($this->role->name)->getLabel();
    // }
}
