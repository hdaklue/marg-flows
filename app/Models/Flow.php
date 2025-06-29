<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Roles\RoleableEntity;
use App\Concerns\Status\HasStagesTrait;
use App\Concerns\Tenant\BelongsToTenant;
use App\Contracts\HasStaticType;
use App\Contracts\Roles\HasParticipants;
use App\Contracts\Roles\Roleable;
use App\Contracts\Status\HasStages;
use App\Enums\FlowStatus;
use BackedEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Flow extends Model implements HasParticipants, HasStages, HasStaticType, Roleable, Sortable
{
    /**
     * @use HasFactory<\Database\Factories\FlowFactory>
     * @use BelongsToTenant<\App\Concerns\Tenant\BelongsToTenant>
     * @use HasStagesTrait<\App\Concerns\Status\HasStagesTrait>
     * @use HasUlids
     * @use RoleableEntity<\App\Concerns\Roles\RoleableEntity>
     * @use SoftDeletes
     * @use SortableTrait<\Spatie\EloquentSortable\SortableTrait>
     */
    use BelongsToTenant,HasFactory,
        HasStagesTrait,
        HasStaticTypeTrait,
        HasUlids ,
        RoleableEntity,
        SoftDeletes,
        SortableTrait;

    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'title',
        'status',
        'is_default',
        'start_date',
        'due_date',
        'completed_at',
    ];

    /* The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => FlowStatus::ACTIVE->value,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeAssignable(Builder $builder)
    {
        return $builder->where('status', '!=', FlowStatus::COMPLETED->value);

    }

    public function scopeByStatus(Builder $builder, string|FlowStatus $status)
    {
        if ($status instanceof BackedEnum) {
            $status = $status->value;
        }

        return $builder->where('status', '=', $status);
    }

    #[Scope]
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', '!=', FlowStatus::COMPLETED->value);
    }

    public function buildSortQuery()
    {
        return static::query()
            ->byStatus($this->status)
            ->byTenant($this->tenant);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            // 'status' => FlowStatus::class,
            'due_date' => 'date',
            'start_date' => 'date',
            'completed_at' => 'date',
        ];
    }
}
