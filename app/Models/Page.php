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
use Eloquent;
use Exception;
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
 * @property string|array<array-key, mixed> $blocks
 * @property string $creator_id
 * @property string $tenant_id
 * @property string $pageable_type
 * @property string $pageable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read User $creator
 * @property-read Model|Eloquent $pageable
 * @property-read Collection<int, ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 *
 * @method static \Database\Factories\PageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereBlocks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page wherePageableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page wherePageableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class Page extends Model implements BelongsToTenantContract, HasStaticType, RoleableEntity, Sidenoteable
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use BelongsToTenant, HasFactory, HasSideNotes, HasStaticTypeTrait, HasUlids, LivesInBusinessDB, ManagesParticipants;

    protected $fillable = [
        'name',
        'blocks',
    ];

    public function pageable(): MorphTo
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

    // public function getTenant(): Tenant
    // {
    //     if ($this->pageable instanceof BelongsToTenantContract) {
    //         return $this->pageable->getTenant();
    //     }
    //     throw new Exception("Can't resolve tenant of {static::class}");
    // }

    public function casts(): array
    {
        return [
            'blocks' => 'json',
        ];
    }
}
