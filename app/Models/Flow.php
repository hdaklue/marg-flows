<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use App\Concerns\Document\ManagesDocuments;
use App\Concerns\HasSideNotes;
use App\Concerns\SentInNotificationTrait;
use App\Concerns\Stage\HasStagesTrait;
use App\Contracts\Document\Documentable;
use App\Contracts\ScopedToTenant;
use App\Contracts\SentInNotification;
use App\Contracts\Sidenoteable;
use App\Contracts\Stage\HasStages;
use App\Contracts\Tenant\BelongsToTenantContract;
use App\Enums\FlowStage;
use App\Facades\MentionService;
use App\Services\Flow\FlowService;
use App\Services\Recency\Concerns\RecentableModel;
use App\Services\Recency\Contracts\Recentable;
use Hdaklue\MargRbac\Concerns\Tenant\BelongsToTenant;
use Hdaklue\Porter\Concerns\ReceivesRoleAssignments;
use Hdaklue\Porter\Multitenancy\Contracts\PorterRoleableContract;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property int $stage
 * @property string|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $canceled_at
 * @property array<array-key, mixed>|null $settings
 * @property string $tenant_id
 * @property string $creator_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $assigned_roles_count
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $documents
 * @property-read int|null $documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SideNote> $sideNotes
 * @property-read int|null $side_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stage> $stages
 * @property-read int|null $stages_count
 * @property-read Tenant $tenant
 *
 * @method static Builder<static>|Flow assignable()
 * @method static Builder<static>|Flow byStage(\App\Enums\FlowStage|string $stage)
 * @method static \Database\Factories\FlowFactory factory($count = null, $state = [])
 * @method static Builder<static>|Flow newModelQuery()
 * @method static Builder<static>|Flow newQuery()
 * @method static Builder<static>|Flow onlyTrashed()
 * @method static Builder<static>|Flow query()
 * @method static Builder<static>|Flow scopeByTenant(\App\Models\Tenant $tenant)
 * @method static Builder<static>|Flow scopeRunning()
 * @method static Builder<static>|Flow whereCanceledAt($value)
 * @method static Builder<static>|Flow whereCompletedAt($value)
 * @method static Builder<static>|Flow whereCreatedAt($value)
 * @method static Builder<static>|Flow whereCreatorId($value)
 * @method static Builder<static>|Flow whereDeletedAt($value)
 * @method static Builder<static>|Flow whereDescription($value)
 * @method static Builder<static>|Flow whereId($value)
 * @method static Builder<static>|Flow whereSettings($value)
 * @method static Builder<static>|Flow whereStage($value)
 * @method static Builder<static>|Flow whereStartedAt($value)
 * @method static Builder<static>|Flow whereTenantId($value)
 * @method static Builder<static>|Flow whereTitle($value)
 * @method static Builder<static>|Flow whereUpdatedAt($value)
 * @method static Builder<static>|Flow withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Flow withoutTrashed()
 *
 * @mixin \Eloquent
 */
final class Flow extends Model implements BelongsToTenantContract, Documentable, HasStages, PorterRoleableContract, Recentable, ScopedToTenant, SentInNotification, Sidenoteable
{
    use BelongsToTenant, HasFactory, HasSideNotes, HasStagesTrait, HasUlids, LivesInOriginalDB, ManagesDocuments, ReceivesRoleAssignments, RecentableModel, SentInNotificationTrait, SoftDeletes;

    // protected $connection = 'mysql';

    // protected $table = 'klueportal.flows';

    protected $fillable = [
        'title',
        'description',
        'stage',
        'is_default',
        'completed_at',
        'canceled_at',
        'started_at',
    ];

    /* The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'stage' => FlowStage::ACTIVE->value,
    ];

    public function getMentionables(): Collection
    {
        return MentionService::getMentionables($this);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all participants (users with role assignments) on this flow.
     */
    public function participants()
    {
        return $this->roleAssignments()->where('assignable_type', 'user');
    }

    public function getPorterTenantKey(): ?string
    {
        return $this->getTenantId();
    }

    /**
     * Get participants as User models.
     */
    public function getParticipants(): Collection
    {
        return $this->participants()
            ->get()
            ->pluck('assignable')
            ->filter();
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class);
    }

    public function activeDeliverables(): HasMany
    {
        return $this->deliverables()->active();
    }

    public function completedDeliverables(): HasMany
    {
        return $this->deliverables()->where('status', 'completed');
    }

    public function overdueDeliverables(): HasMany
    {
        return $this->deliverables()->overdue();
    }

    public function setAsCompleted()
    {
        $this->update([
            'stage' => FlowStage::COMPLETED->value,
            'completed_at' => now(),
            'canceled_at' => null,
        ]);
    }

    public function setAsCanceled()
    {
        $this->update([
            'stage' => FlowStage::CANCELED->value,
            'completed_at' => null,
            'canceled_at' => now(),
        ]);
    }

    public function setStatus(FlowStage $status)
    {
        $this->update([
            'stage' => $status->value,
            'completed_at' => null,
            'canceled_at' => null,
        ]);
    }

    // public function buildSortQuery()
    // {
    //     return self::query()
    //         ->byStatus(FlowStatus::from($this->status))
    //         ->byTenant($this->tenant);
    // }

    // Deliverable Helper Methods
    public function getDeliverablesCount(): int
    {
        return $this->deliverables()->count();
    }

    public function getActiveDeliverablesCount(): int
    {
        return $this->activeDeliverables()->count();
    }

    public function getCompletedDeliverablesCount(): int
    {
        return $this->completedDeliverables()->count();
    }

    public function getOverdueDeliverablesCount(): int
    {
        return $this->overdueDeliverables()->count();
    }

    public function getDeliverablesProgress(): float
    {
        $total = $this->getDeliverablesCount();

        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->getCompletedDeliverablesCount();

        return round(($completed / $total) * 100, 1);
    }

    public function hasOverdueDeliverables(): bool
    {
        return $this->getOverdueDeliverablesCount() > 0;
    }

    public function hasActiveDeliverables(): bool
    {
        return $this->getActiveDeliverablesCount() > 0;
    }

    public function canBeCompleted(): bool
    {
        // Flow can only be completed if all deliverables are completed
        return $this->getActiveDeliverablesCount() === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentLabel(): ?string
    {
        return $this->title;
    }

    #[Scope]
    protected function forParticipant(Builder $builder, User $user, Tenant $tenant): Builder
    {
        $ids = (new FlowService)->getForParticipant($user, $tenant)->pluck('id')->toArray();

        return $builder->whereIn('id', $ids);
    }

    #[Scope]
    protected function assignable(Builder $builder): Builder
    {
        return $builder->where('stage', '!=', FlowStage::COMPLETED->value);
    }

    #[Scope]
    protected function scopeRunning(Builder $query): Builder
    {
        return $query->whereNotIn('stage', [
            FlowStage::COMPLETED->value,
            FlowStage::CANCELED->value,
            FlowStage::PAUSED->value,
        ]);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            // 'due_date' => 'datetime',
            // 'start_date' => 'datetime',
            'completed_at' => 'date',
            'canceled_at' => 'date',
        ];
    }
}
