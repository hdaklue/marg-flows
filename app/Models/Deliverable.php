<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\DeliverableSpecificationCast;
use App\Concerns\Database\LivesInBusinessDB;
use App\Concerns\HasSideNotes;
use App\Concerns\Stage\HasStagesTrait;
use App\Contracts\ScopedToTenant;
use App\Contracts\Sidenoteable;
use App\Contracts\Stage\HasStages;
use App\Contracts\Tenant\BelongsToTenantContract;
use App\Enums\AssigneeRole;
use App\Enums\Deliverable\DeliverableFormat;
use App\Enums\Deliverable\DeliverableStatus;
use App\ValueObjects\Deliverable\DeliverableSpecification;
use Hdaklue\MargRbac\Concerns\Tenant\BelongsToTenant;
use Hdaklue\Porter\Concerns\ReceivesRoleAssignments;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property DeliverableFormat $format
 * @property string $type
 * @property DeliverableStatus $status
 * @property int $priority
 * @property int $order_column
 * @property Carbon|null $start_date
 * @property Carbon|null $success_date
 * @property Carbon|null $completed_at
 * @property DeliverableSpecification $format_specifications
 * @property array|null $settings
 * @property string $flow_id
 * @property string|null $stage_id
 * @property string $creator_id
 * @property string $tenant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Flow $flow
 * @property-read User $creator
 * @property-read Stage|null $stage
 * @property-read Collection<int, DeliverableVersion> $versions
 * @property-read DeliverableVersion|null $currentVersion
 * @property-read Collection<int, ModelHasRole> $participants
 * @property-read Collection<int, Role> $assignedRoles
 * @property-read Collection<int, SideNote> $sideNotes
 * @property-read Tenant $tenant
 *
 * @method static \Database\Factories\DeliverableFactory factory($count = null, $state = [])
 */
final class Deliverable extends Model implements
    BelongsToTenantContract,
    HasStages,
    RoleableEntity,
    ScopedToTenant,
    Sidenoteable
{
    /** @use HasFactory<\Database\Factories\DeliverableFactory> */
    use BelongsToTenant, HasFactory, HasSideNotes, HasStagesTrait, HasUlids, LivesInBusinessDB, ReceivesRoleAssignments, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'format',
        'type',
        'status',
        'priority',
        'order_column',
        'start_date',
        'success_date',
        'format_specifications',
        'settings',
        'flow_id',
        'stage_id',
        'creator_id',
    ];

    protected $attributes = [
        'status' => DeliverableStatus::REQUESTED->value,
        'priority' => 3,
        'order_column' => 0,
    ];

    // Relationships
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DeliverableVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DeliverableVersion::class)->latest('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DeliverableVersion::class)->orderByDesc('version_number');
    }

    // Status Management
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => DeliverableStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => DeliverableStatus::IN_PROGRESS,
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    public function markAsReview(): void
    {
        $this->update([
            'status' => DeliverableStatus::REVIEW,
        ]);
    }

    public function requestRevision(): void
    {
        $this->update([
            'status' => DeliverableStatus::REVIEW,
        ]);
    }

    public function canTransitionTo(DeliverableStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function transitionTo(DeliverableStatus $newStatus): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->update(['status' => $newStatus]);

        if ($newStatus === DeliverableStatus::COMPLETED) {
            $this->update(['completed_at' => now()]);
        }

        return true;
    }

    // Format & Specifications
    public function getFormatSpecifications(): null|DeliverableSpecification
    {
        return $this->format_specifications;
    }

    public function setFormatSpecificationsFromConfig(): void
    {
        $config = config("deliverables.{$this->format->value}.{$this->type}");

        if (empty($config)) {
            return;
        }

        // The cast will automatically convert the config array to the appropriate ValueObject
        $this->format_specifications = $config;
        $this->save();
    }

    public function getSpecification(string $key, mixed $default = null): mixed
    {
        if (!$this->format_specifications) {
            return $default;
        }

        return data_get($this->format_specifications->toArray(), $key, $default);
    }

    public function validateFile(array $fileData): bool
    {
        if (!$this->format_specifications) {
            return true; // No specifications to validate against
        }

        return $this->format_specifications->validate($fileData);
    }

    public function getSpecificationRequirements(): array
    {
        return $this->format_specifications?->getRequirements() ?? [];
    }

    public function getSpecificationName(): string
    {
        return $this->format_specifications?->getName() ?? 'Unknown Specification';
    }

    public function getSpecificationDescription(): string
    {
        return $this->format_specifications?->getDescription() ?? '';
    }

    public function getSpecificationTags(): array
    {
        return $this->format_specifications?->getTags() ?? [];
    }

    // Progress & Status Helpers
    public function isOverdue(): bool
    {
        return $this->success_date && $this->success_date->isPast() && !$this->status->isComplete();
    }

    public function isDue(): bool
    {
        if (!$this->success_date) {
            return false;
        }

        return $this->success_date->isToday() || $this->success_date->isTomorrow();
    }

    public function getDaysUntilDue(): null|int
    {
        if (!$this->success_date) {
            return null;
        }

        return now()->diffInDays($this->success_date, false);
    }

    public function getProgressPercentage(): int
    {
        return $this->status->getProgressPercentage();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isComplete(): bool
    {
        return $this->status->isComplete();
    }

    public function requiresAction(): bool
    {
        return $this->status->requiresAction();
    }

    // Versioning
    public function createNewVersion(array $data = []): DeliverableVersion
    {
        $nextVersionNumber = $this->versions()->max('version_number') + 1;

        return $this->versions()->create(array_merge($data, [
            'version_number' => $nextVersionNumber,
            'created_by' => auth()->id(),
        ]));
    }

    public function getVersionCount(): int
    {
        return $this->versions()->count();
    }

    public function hasVersions(): bool
    {
        return $this->versions()->exists();
    }

    // Participant Management Extensions
    public function getAssignees(): Collection
    {
        return $this->getParticipantsByRole(AssigneeRole::ASSIGNEE);
    }

    public function getReviewers(): Collection
    {
        return $this->getParticipantsByRole(AssigneeRole::REVIEWER);
    }

    public function getApprovers(): Collection
    {
        return $this->getParticipantsByRole(AssigneeRole::APPROVER);
    }

    public function getObservers(): Collection
    {
        return $this->getParticipantsByRole(AssigneeRole::OBSERVER);
    }

    public function assignTo(string|User $user, AssigneeRole $role = AssigneeRole::ASSIGNEE): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $this->assignParticipant($userId, $role);
    }

    public function isAssignedTo(string|User $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->hasParticipantWithRole($userId, AssigneeRole::ASSIGNEE);
    }

    public function canBeEditedBy(string|User $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return (
            $this->isAssignedTo($userId)
            || $this->creator_id === $userId
            || $this->flow->hasParticipantWithRole($userId, AssigneeRole::ASSIGNEE)
        );
    }

    // RoleableEntity Implementation
    public function getMorphClass(): string
    {
        return 'deliverable';
    }

    public function getTenantId(): string|int
    {
        return $this->tenant_id;
    }

    protected function casts(): array
    {
        return [
            'format_specifications' => DeliverableSpecificationCast::class,
            'settings' => 'array',
            'start_date' => 'datetime',
            'success_date' => 'datetime',
            'completed_at' => 'datetime',
            'format' => DeliverableFormat::class,
            'status' => DeliverableStatus::class,
        ];
    }

    // Scopes
    #[Scope]
    protected function forUser($query, string|User $user)
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('model_id', $userId);
        });
    }

    #[Scope]
    protected function byStatus($query, DeliverableStatus $status)
    {
        return $query->where('status', $status);
    }

    #[Scope]
    protected function byFormat($query, DeliverableFormat $format)
    {
        return $query->where('format', $format);
    }

    #[Scope]
    protected function byPriority($query, int $priority)
    {
        return $query->where('priority', $priority);
    }

    #[Scope]
    protected function highPriority($query)
    {
        return $query->where('priority', '>=', 4);
    }

    #[Scope]
    protected function overdue($query)
    {
        return $query->where(
            'success_date',
            '<',
            now(),
        )->whereNotIn('status', [DeliverableStatus::COMPLETED]);
    }

    #[Scope]
    protected function dueToday($query)
    {
        return $query->whereDate(
            'success_date',
            today(),
        )->whereNotIn('status', [DeliverableStatus::COMPLETED]);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->whereIn('status', [
            DeliverableStatus::IN_PROGRESS,
            DeliverableStatus::REVIEW,
            DeliverableStatus::REVISION_REQUESTED,
        ]);
    }

    #[Scope]
    protected function ordered($query, string $direction = 'asc')
    {
        return $query->orderBy('order_column', $direction);
    }
}
