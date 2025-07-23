<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Role\ManagesParticipants;
use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use App\Contracts\Tenant\BelongsToTenantContract;
use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property array<array-key, mixed> $blocks
 * @property string $creator_id
 * @property string $pageable_type
 * @property string $pageable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read \App\Models\User $creator
 * @property-read Model|\Eloquent $pageable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
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
 * @mixin \Eloquent
 */
final class Page extends Model implements HasStaticType, RoleableEntity
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory, HasStaticTypeTrait, HasUlids, ManagesParticipants;

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

    public function getTenant(): Tenant
    {
        if ($this->pageable instanceof BelongsToTenantContract) {
            return $this->pageable->getTenant();
        }
        throw new Exception("Can't resolve tenant of {static::class}");
    }

    public function casts(): array
    {
        return [
            'blocks' => 'json',
        ];
    }
}
