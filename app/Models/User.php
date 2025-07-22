<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Role\CanBeAssignedToEntity;
use App\Concerns\Tenant\HasActiveTenant;
use App\Contracts\Role\AssignableEntity;
use App\Enums\Account\AccountType;
use App\Facades\RoleManager;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

use function ucwords;

/**
 * 
 *
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
 * @property AccountType $accout_type
 * @property-read mixed $avatar
 * @property-read Collection<int, \App\Models\Tenant> $createdTenants
 * @property-read int|null $created_tenants_count
 * @property-read Collection<int, \App\Models\Flow> $flows
 * @property-read int|null $flows_count
 * @property-read Collection<int, \App\Models\MemberInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read mixed $inviter_name
 * @property-read \App\Models\LoginLog|null $latestLogin
 * @property-read Collection<int, \App\Models\LoginLog> $logins
 * @property-read int|null $logins_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\MemberInvitation|null $receivedInvitation
 * @property-read Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @method static Builder<static>|User appAdmin()
 * @method static Builder<static>|User assignedTo(\App\Contracts\Role\RoleableEntity $entity)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User memberOf(\App\Models\Tenant $tenant)
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User notAssignedTo(\App\Contracts\Role\RoleableEntity $entity)
 * @method static Builder<static>|User notMemberOf(\App\Models\Tenant $tenant)
 * @method static Builder<static>|User query()
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
 * @mixin IdeHelperUser
 * @mixin \Eloquent
 */
final class User extends Authenticatable implements AssignableEntity, FilamentUser, HasAvatar, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use CanBeAssignedToEntity,
        HasActiveTenant,
        HasFactory,
        HasUlids,
        Notifiable;

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
        'timezone',
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

    public function canAccessPanel(Panel $panel): bool
    {

        if ($panel->getId() === 'admin') {

            return $this->canAccessAdmin() ?: abort(404);
        }

        return true;
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->activeTenant() ?? $this->getAssignedTenants()->first();
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

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    // public function tenants(): BelongsToMany
    // {
    //     return $this->belongsToMany(Tenant::class)->using(TenantUser::class);
    // }

    public function getTenants(Panel $panel): SupportCollection
    {

        return $this->getAssignedTenants();
    }

    public function getAssignedTenants()
    {
        return RoleManager::getAssignedEntitiesByType($this, Relation::getMorphAlias(Tenant::class));
        // return $this->roleAssignments()
        //     ->where('roleable_type', Relation::getMorphAlias(Tenant::class))
        //     ->where('model_type', $this->getMorphClass())
        //     ->where('model_id', $this->getKey())
        //     ->with('roleable')
        //     ->get()->pluck('roleable');
    }

    public function logins(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function latestLogin(): HasOne
    {
        return $this->hasOne(LoginLog::class)->latestOfMany();
    }

    public function receivedInvitation(): HasOne
    {
        return $this->hasOne(MemberInvitation::class, 'receiver_id');
    }

    public function updateLastLogin(string $userAgent, string $ip): self
    {
        $this->logins()->create([
            'user_agent' => $userAgent,
            'ip_address' => $ip,
        ]);

        return $this;
    }

    #[Scope]
    public function scopeNotMemberOf(Builder $builder, Tenant $tenant): Builder
    {
        return $builder->whereDoesntHave('tenants', function ($query) use ($tenant) {
            $query->where('tenants.id', '=', $tenant->id);
        });
    }

    #[Scope]
    public function scopeMemberOf(Builder $builder, Tenant $tenant): Builder
    {
        return $builder->whereHas('tenants', function ($query) use ($tenant) {
            $query->where('tenants.id', '=', $tenant->id);
        });
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(MemberInvitation::class, 'sender_id');
    }

    public function isAppAdmin(): bool
    {
        return $this->account_type === AccountType::ADMIN->value;
    }

    public function isAppManager(): bool
    {
        return $this->account_type === AccountType::MANAGER->value;
    }

    public function isAppUser(): bool
    {
        return $this->account_type === AccountType::USER->value;
    }

    public function createdTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'creator_id');
    }

    #[Scope]
    public function scopeAppAdmin(Builder $builder): Builder
    {
        return $builder->where('account_type', AccountType::ADMIN->value);
    }

    #[Scope]
    public function appUser(Builder $builder): Builder
    {
        return $builder->where('account_type', AccountType::USER->value);
    }

    // public function getTenants(Panel $panel): Collection
    // {
    //     return $this->tenants;
    // }

    public function getFilamentAvatarUrl(): ?string
    {

        return null;
    }

    public function canAccessAdmin(): bool
    {
        return $this->isAppAdmin() || $this->isAppManager();
    }

    /**
     * Summary of canAccessTenant.
     *
     * @param  mixed  $tenant
     */
    public function canAccessTenant($tenant): bool
    {

        return $this->isAssignedTo($tenant);
    }

    protected function inviterName(): Attribute
    {

        return Attribute::make(
            get: fn () => $this->load('receivedInvitation')->receivedInvitation->sender->name ?? null,
        );

    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucwords($value),
        );
    }

    protected function avatar(): Attribute
    {

        return Attribute::make(
            get: fn () => Filament::getUserAvatarUrl($this),
        );
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
            'accout_type' => AccountType::class,
        ];
    }
}
