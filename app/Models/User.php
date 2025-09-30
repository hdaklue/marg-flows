<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Account\AccountType;
use App\Services\Avatar\AvatarService;
use App\Services\Timezone;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Hdaklue\MargRbac\Models\RbacUser;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Contracts\RoleContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $account_type
 * @property string $password
 * @property string|null $timezone
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $active_tenant_id
 * @property-read mixed $avatar
 * @property-read Collection<int, Tenant> $createdTenants
 * @property-read int|null $created_tenants_count
 * @property-read Collection<int, Flow> $flows
 * @property-read int|null $flows_count
 * @property-read Collection<int, MemberInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read mixed $inviter_name
 * @property-read LoginLog|null $latestLogin
 * @property-read Collection<int, LoginLog> $logins
 * @property-read int|null $logins_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read MemberInvitation|null $receivedInvitation
 * @property-read Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 *
 * @method static Builder<static>|User appUser()
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User scopeAppAdmin()
 * @method static Builder<static>|User scopeAssignedTo(RoleableEntity $entity)
 * @method static Builder<static>|User scopeMemberOf(\App\Models\Tenant $tenant)
 * @method static Builder<static>|User scopeNotAssignedTo(RoleableEntity $entity)
 * @method static Builder<static>|User scopeNotMemberOf(\App\Models\Tenant $tenant)
 * @method static Builder<static>|User whereAccountType($value)
 * @method static Builder<static>|User whereActiveTenantId($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereTimezone($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class User extends RbacUser implements FilamentUser, HasTenants
{
    use Notifiable;

    protected static $factory = UserFactory::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
        'active_tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // protected $with = ['profile'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function updateProfileAvatar(string $avatarFileName): void
    {
        $this->loadMissing('profile')->profile->update([
            'avatar' => $avatarFileName,
        ]);
    }

    public function getTimeZone(): string
    {
        return $this->loadMissing('profile')->profile->getAttribute('timezone');
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->activeTenant() ?? $this->getAssignedTenants()->first();
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'id')->chaperone();
    }

    /**
     * Get all flows where this user has any role.
     */
    public function flows(): MorphToMany
    {
        return $this->morphedByMany(
            Flow::class,
            'roleable',
            config('role.table_names.model_has_roles'),
            'model_id',
            'roleable_id',
        )->withPivot(['role_id', 'tenant_id']);
    }

    // // public function tenants(): BelongsToMany
    // // {
    // //     return $this->belongsToMany(Tenant::class)->using(TenantUser::class);
    // // }

    public function getTenants(Panel $panel): SupportCollection
    {
        return $this->getAssignedTenants();
    }

    /**
     * Check if the user can view a flow.
     */
    public function canViewFlow(Flow $flow): bool
    {
        return $this->isAssignedTo($flow);
    }

    /**
     * Check if the user has a specific role in a flow.
     */
    public function hasRoleOnFlow(Flow $flow, RoleContract $role): bool
    {
        return $this->hasAssignmentOn($flow, $role);
    }

    public function getAvatarFileName(): ?string
    {
        return $this->load('profile')->profile?->avatar;
    }

    public function getAvatarUrl(): string
    {
        return AvatarService::generateAvatarUrl($this);
    }

    public function displayTimeZone(): string
    {
        return Timezone::displayTimezone($this->getTimezone());
    }

    /**
     * Get all tenants created by this user.
     */
    public function createdTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'creator_id');
    }

    /**
     * Get the user's notifications using the main database connection.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * Get the model's morph class.
     */
    public function getMorphClass(): string
    {
        return 'user';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
        ];
    }
}
