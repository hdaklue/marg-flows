<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Roles\RoleableEntity;
use App\Concerns\Tenant\BelongsToTenant;
use App\Contracts\Roles\HasParticipants;
use App\Enums\FlowStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flow extends Model implements HasParticipants
{
    /** @use HasFactory<\Database\Factories\FlowFactory>
     * @use BelongsToTenant, HasUlids, RoleableEntity, SoftDeletes;
     */
    use BelongsToTenant, HasFactory, HasUlids, RoleableEntity, SoftDeletes;

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

    public function scopeActiveOrScheduledByTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->whereBelongsTo($tenant, 'tenant')
            ->whereIn('status', [FlowStatus::ACTIVE->value, FlowStatus::SCHEDULED->value]);
    }

    #[Scope]
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', '!=', FlowStatus::COMPLETED->value);
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
