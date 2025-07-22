<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Role\ManagesParticipants;
use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use App\Enums\Role\RoleEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property int $active
 * @property string $creator_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Flow> $flows
 * @property-read int|null $flows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $systemRoles
 * @property-read int|null $system_roles_count
 * @method static \Database\Factories\TenantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereUpdatedAt($value)
 * @mixin IdeHelperTenant
 * @mixin \Eloquent
 */
final class Tenant extends Model implements HasStaticType, RoleableEntity
{
    use HasFactory, HasStaticTypeTrait, HasUlids, ManagesParticipants;

    protected $fillable = ['name'];

    // public function members(): BelongsToMany
    // {
    //     return $this->belongsToMany(User::class)->using(TenantUser::class);
    // }

    /**
     * Enforces the RoleableEntity.
     *
     * @see RoleableEntity contract
     */
    public function getTenant(): Tenant
    {
        return $this;
    }

    /**
     * Enforces the RoleableEntity.
     *
     * @see RoleableEntity
     */
    public function getTenantId(): string
    {
        return $this->getKey();
    }

    public function systemRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'tenant_id');
    }

    public function getSystemRoles(): Collection
    {
        return $this->systemRoles()->get();
    }

    public function systemRoleByName(string|RoleEnum $role): Role
    {
        if ($role instanceof RoleEnum) {
            $role = $role->value;
        }

        // @phpstan-ignore return.type
        return $this->systemRoles()->where('name', $role)->firstOrFail();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getTypeName(): string
    {
        return 'Team';
    }

    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class);
    }
}
