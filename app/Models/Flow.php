<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasSideNotes;
use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Progress\HasTimeProgress;
use App\Concerns\Role\ManagesParticipants;
use App\Concerns\Stage\HasStagesTrait;
use App\Concerns\Tenant\BelongsToTenant;
use App\Contracts\HasStaticType;
use App\Contracts\Progress\TimeProgressable;
use App\Contracts\Role\RoleableEntity;
use App\Contracts\ScopedToTenant;
use App\Contracts\Sidenoteable;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * 
 *
 * @property string $id
 * @property string $title
 * @property int $status
 * @property int $is_default
 * @property int $order_column
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property Carbon|null $completed_at
 * @property Carbon|null $canceled_at
 * @property array<array-key, mixed>|null $settings
 * @property mixed $blocks
 * @property string $tenant_id
 * @property string $creator_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read \App\Models\User $creator
 * @property-read string $progress_completed_date
 * @property-read string $progress_due_date
 * @property-read string $progress_start_date
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SideNote> $sideNotes
 * @property-read int|null $side_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stage> $stages
 * @property-read int|null $stages_count
 * @property-read \App\Models\Tenant $tenant
 * @method static Builder<static>|Flow assignable()
 * @method static Builder<static>|Flow byStage(\App\Enums\FlowStatus|string $status)
 * @method static Builder<static>|Flow byStatus(\App\Enums\FlowStatus|string $status)
 * @method static Builder<static>|Flow byTenant(\App\Models\Tenant $tenant)
 * @method static \Database\Factories\FlowFactory factory($count = null, $state = [])
 * @method static Builder<static>|Flow forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static Builder<static>|Flow newModelQuery()
 * @method static Builder<static>|Flow newQuery()
 * @method static Builder<static>|Flow onlyTrashed()
 * @method static Builder<static>|Flow ordered(string $direction = 'asc')
 * @method static Builder<static>|Flow query()
 * @method static Builder<static>|Flow running()
 * @method static Builder<static>|Flow whereBlocks($value)
 * @method static Builder<static>|Flow whereCanceledAt($value)
 * @method static Builder<static>|Flow whereCompletedAt($value)
 * @method static Builder<static>|Flow whereCreatedAt($value)
 * @method static Builder<static>|Flow whereCreatorId($value)
 * @method static Builder<static>|Flow whereDeletedAt($value)
 * @method static Builder<static>|Flow whereDueDate($value)
 * @method static Builder<static>|Flow whereId($value)
 * @method static Builder<static>|Flow whereIsDefault($value)
 * @method static Builder<static>|Flow whereOrderColumn($value)
 * @method static Builder<static>|Flow whereSettings($value)
 * @method static Builder<static>|Flow whereStartDate($value)
 * @method static Builder<static>|Flow whereStatus($value)
 * @method static Builder<static>|Flow whereTenantId($value)
 * @method static Builder<static>|Flow whereTitle($value)
 * @method static Builder<static>|Flow whereUpdatedAt($value)
 * @method static Builder<static>|Flow withTrashed()
 * @method static Builder<static>|Flow withoutTrashed()
 * @mixin \Eloquent
 */
final class Flow extends Model implements HasStages, HasStaticType, RoleableEntity, ScopedToTenant, Sidenoteable, Sortable, TimeProgressable
{
    use BelongsToTenant,
        HasFactory,
        HasSideNotes,
        HasStagesTrait,
        HasStaticTypeTrait ,
        HasTimeProgress,
        HasUlids,
        ManagesParticipants,
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

    public function systemRoleByName(string $name): Role
    {
        return $this->getTenant()->systemRoleByName($name);
    }

    public function getSystemRoles(): Collection
    {
        return $this->getTenant()->getSystemRoles();
    }

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
        return $query->whereNotIn('status', [FlowStatus::COMPLETED->value, FlowStatus::CANCELED->value, FlowStatus::PAUSED->value]);
    }

    public function buildSortQuery()
    {
        return self::query()
            ->byStatus(FlowStatus::from($this->status))
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
