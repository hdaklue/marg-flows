<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use App\Concerns\HasSideNotes;
use App\Concerns\SentInNotificationTrait;
use App\Contracts\SentInNotification;
use App\Contracts\Sidenoteable;
use App\Contracts\Tenant\BelongsToTenantContract;
use App\Services\Document\Collections\DocumentBlocksCollection;
use App\Services\Recency\Concerns\RecentableModel;
use App\Services\Recency\Contracts\Recentable;
use Eloquent;
use Exception;
use Hdaklue\MargRbac\Concerns\Tenant\BelongsToTenant;
use Hdaklue\Porter\Concerns\ReceivesRoleAssignments;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property array<array-key, mixed> $blocks
 * @property string $creator_id
 * @property string $tenant_id
 * @property string $documentable_type
 * @property string $documentable_id
 * @property string|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read mixed $block_collection
 * @property-read User|null $creator
 * @property-read Model|Eloquent $documentable
 * @property-read Collection<int, ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read Collection<int, SideNote> $sideNotes
 * @property-read int|null $side_notes_count
 * @property-read Tenant|null $tenant
 *
 * @method static Builder<static>|Document active()
 * @method static Builder<static>|Document archived()
 * @method static \Database\Factories\DocumentFactory factory($count = null, $state = [])
 * @method static Builder<static>|Document forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static Builder<static>|Document newModelQuery()
 * @method static Builder<static>|Document newQuery()
 * @method static Builder<static>|Document query()
 * @method static Builder<static>|Document scopeByTenant(\App\Models\Tenant $tenant)
 * @method static Builder<static>|Document whereArchivedAt($value)
 * @method static Builder<static>|Document whereBlocks($value)
 * @method static Builder<static>|Document whereCreatedAt($value)
 * @method static Builder<static>|Document whereCreatorId($value)
 * @method static Builder<static>|Document whereDocumentableId($value)
 * @method static Builder<static>|Document whereDocumentableType($value)
 * @method static Builder<static>|Document whereId($value)
 * @method static Builder<static>|Document whereName($value)
 * @method static Builder<static>|Document whereTenantId($value)
 * @method static Builder<static>|Document whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class Document extends Model implements BelongsToTenantContract, Recentable, RoleableEntity, SentInNotification, Sidenoteable
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use BelongsToTenant, HasFactory, HasSideNotes, HasUlids, LivesInOriginalDB, ReceivesRoleAssignments, RecentableModel, SentInNotificationTrait;

    protected $fillable = [
        'name',
        'blocks',
        'archived_at',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getRecentLabel(): ?string
    {
        return $this->getAttribute('name');
    }

    public function updateBlocks(array|string $data)
    {
        $this->setAttribute('blocks', $data);
        $this->save();
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function isArchived(): bool
    {
        return ! empty($this->getAttribute('archived_at'));
    }

    public function casts(): array
    {
        return [
            'blocks' => 'json',
        ];
    }

    /**
     * Get the file storage identifier for this document.
     * Uses the tenant ID as the storage identifier.
     */
    // public function getFileStorageIdentifier(): string
    // {
    //     return (string) $this->getTenantId();
    // }

    #[Scope]
    protected function archived(Builder $builder): Builder
    {
        return $builder->whereNotNull('archived_at');
    }

    #[Scope]
    protected function active(Builder $builder): Builder
    {
        return $builder->whereNull('archived_at');
    }

    // public function getTenant(): Tenant
    // {
    //     if ($this->Documentable instanceof BelongsToTenantContract) {
    //         return $this->Documentable->getTenant();
    //     }
    //     throw new Exception("Can't resolve tenant of {static::class}");
    // }

    /**
     * Get the user's first name.
     */
    protected function blockCollection(): Attribute
    {
        return Attribute::make(get: fn () => DocumentBlocksCollection::fromEditorJS($this->getAttribute(
            'blocks',
        )));
    }
}
