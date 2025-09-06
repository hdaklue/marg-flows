<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasStaticTypeTrait;
use App\Contracts\HasStaticType;
use Database\Factories\TenantFactory;
use Hdaklue\MargRbac\Models\RbacTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property int $active
 * @property string $creator_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read User $creator
 * @property-read Collection<int, Flow> $flows
 * @property-read int|null $flows_count
 * @property-read Collection<int, ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read Collection<int, Role> $systemRoles
 * @property-read int|null $system_roles_count
 *
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
 *
 * @mixin \Eloquent
 */
final class Tenant extends RbacTenant implements HasStaticType
{
    use HasStaticTypeTrait;

    protected static $factory = TenantFactory::class;

    protected $fillable = ['name'];

    /**
     * Get the model's morph class.
     */
    public function getMorphClass(): string
    {
        return 'tenant';
    }

    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class);
    }
}
