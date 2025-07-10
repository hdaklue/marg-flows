<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Concerns\Role\ManagesParticipants;
use App\Contracts\HasStaticType;
use App\Contracts\Role\RoleableEntity;
use BackedEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

final class Tenant extends Model implements HasStaticType, RoleableEntity
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    /** @use HasUlids */
    use HasFactory, HasStaticTypeTrait, HasUlids, ManagesParticipants;

    protected $fillable = ['name'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(TenantUser::class);
    }

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

    public function removeMember(User|array $user)
    {
        $this->members()->detach($user);
    }

    public function addMember(User|array $user)
    {
        $this->members()->attach($user);
    }

    public function systemRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'tenant_id');
    }

    public function getSystemRoles(): Collection
    {
        return $this->systemRoles()->get();
    }

    public function systemRoleByName(string|BackedEnum $role): ?Role
    {
        if ($role instanceof BackedEnum) {
            $role = $role->value;
        }

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
