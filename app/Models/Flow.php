<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Progress\HasTimeProgress;
use App\Concerns\Roles\RoleableEntity;
use App\Concerns\Stage\HasStagesTrait;
use App\Concerns\Tenant\BelongsToTenant;
use App\Contracts\HasStaticType;
use App\Contracts\Progress\TimeProgressable;
use App\Contracts\Roles\HasParticipants;
use App\Contracts\Roles\Roleable;
use App\Contracts\Stage\HasStages;
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

class Flow extends Model implements HasParticipants, HasStages, HasStaticType, Roleable, Sortable, TimeProgressable
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
    use BelongsToTenant,
        HasFactory,
        HasStagesTrait,
        HasStaticTypeTrait,
        HasTimeProgress ,
        HasUlids,
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
        'canceled_at',
        'blocks',
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

    public function setAsCompleted()
    {
        $this->update([
            'status' => FlowStatus::COMPLETED->value,
            'completed_at' => now(),
            'canceled_at' => null,
        ]);
    }

    public function setAsCanceled()
    {
        $this->update([
            'status' => FlowStatus::CANCELED->value,
            'completed_at' => null,
            'canceled_at' => now(),
        ]);
    }

    public function setStatus(FlowStatus $status)
    {

        $this->update([
            'status' => $status->value,
            'completed_at' => null,
            'canceled_at' => null,
        ]);
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
            'blocks' => 'json:unicode',
            'due_date' => 'datetime',
            'start_date' => 'datetime',
            'completed_at' => 'date',
            'canceled_at' => 'date',
        ];
    }
}
