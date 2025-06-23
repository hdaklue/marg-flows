<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Roles\RoleableEntity;
use App\Concerns\Tenant\BelongsToTenant;
use App\Contracts\Roles\HasParticipants;
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

class Flow extends Model implements HasParticipants, Sortable
{
    /** @use HasFactory<\Database\Factories\FlowFactory>
     * @use BelongsToTenant, HasUlids, RoleableEntity, SoftDeletes;
     */
    use BelongsToTenant,HasFactory, HasUlids, RoleableEntity, SoftDeletes, SortableTrait;

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopeAssignable(Builder $builder)
    {
        return $builder->whereIn('status', [FlowStatus::ACTIVE->value, FlowStatus::SCHEDULED->value]);

    }

    public function scopeByStatus(Builder $builder, string|FlowStatus $status)
    {
        if ($status instanceof BackedEnum) {
            $status = $status->value;
        }

        return $builder->where('status', '=', $status);
    }

    // public function scopeAssignableByTenant(Builder $query, Tenant $tenant): Builder
    // {
    //     return $query->byTenant($tenant)
    //         ->assignable();
    // }

    #[Scope]
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', '!=', FlowStatus::COMPLETED->value);
    }

    public function buildSortQuery()
    {
        return static::query()->byTenant($this->tenant)
            ->byStatus($this->status);
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
