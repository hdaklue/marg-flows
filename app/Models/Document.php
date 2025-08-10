<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use App\Concerns\HasSideNotes;
use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Role\ManagesParticipants;
use App\Concerns\Tenant\BelongsToTenant;
use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use App\Contracts\Sidenoteable;
use App\Contracts\Tenant\BelongsToTenantContract;
use App\Services\Document\Collections\DocumentBlocksCollection;
use Eloquent;
use Exception;
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
 * @property string $creator_id
 * @property string $tenant_id
 * @property string $documentable_type
 * @property string $documentable_id
 * @property DocumentBlocksCollection $blockCollection
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read User $creator
 * @property-read Model|Eloquent $documentable
 * @property-read Flow $flow
 * @property-read Collection<int, ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 *
 * @method static \Database\Factories\DocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereBlocks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Document extends Model implements BelongsToTenantContract, HasStaticType, RoleableEntity, Sidenoteable
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use BelongsToTenant, HasFactory, HasSideNotes, HasStaticTypeTrait, HasUlids, LivesInBusinessDB, ManagesParticipants;

    protected $fillable = [
        'name',
        'blocks',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function casts(): array
    {
        return [
            'blocks' => 'json',
        ];
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
        return Attribute::make(
            get: fn () => DocumentBlocksCollection::fromEditorJS($this->getAttribute('blocks')),
        );
    }
}
