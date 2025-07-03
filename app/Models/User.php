<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Concerns\Roles\HasEntityAwareRoles;
use App\Enums\Account\AccountType;
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasEntityAwareRoles, HasFactory, HasUlids, Notifiable;

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
        return $this->activeTenant()->first() ?? $this->tenants()->first();
    }

    /**
     * Get all flows where this user has any role
     */
    public function flows(): MorphToMany
    {
        return $this->morphedByMany(
            Flow::class,
            'roleable',
            config('permission.table_names.model_has_roles'),
            'model_id',
            'roleable_id',
        )->withPivot(['role_id', 'tenant_id']);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)->using(TenantUser::class);
    }

    public function activeTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'active_tenant_id');
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

    public function switchActiveTenant(Tenant $tenant): self
    {
        $this->activeTenant()->associate($tenant);
        $this->save();

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

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    public function getFilamentAvatarUrl(): ?string
    {

        return null;
    }

    public function canAccessAdmin(): bool
    {
        return $this->isAppAdmin() || $this->isAppManager();
    }

    public function canAccessTenant(Model $tenant): bool
    {

        return $this->tenants()->whereKey($tenant)->exists();
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
            get: fn ($value) => \ucwords($value),
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
