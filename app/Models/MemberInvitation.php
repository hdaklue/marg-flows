<?php

declare(strict_types=1);

namespace App\Models;

use Hdaklue\MargRbac\Concerns\Database\LivesInRbacDB;
use Hdaklue\Porter\Casts\RoleCast;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $sender_id
 * @property string $receiver_id
 * @property array<array-key, mixed> $role_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $receiver
 * @property-read User $sender
 *
 * @method static Builder<static>|MemberInvitation newModelQuery()
 * @method static Builder<static>|MemberInvitation newQuery()
 * @method static Builder<static>|MemberInvitation query()
 * @method static Builder<static>|MemberInvitation sentBy(\App\Models\User $user)
 * @method static Builder<static>|MemberInvitation whereCreatedAt($value)
 * @method static Builder<static>|MemberInvitation whereId($value)
 * @method static Builder<static>|MemberInvitation whereReceiverId($value)
 * @method static Builder<static>|MemberInvitation whereRoleData($value)
 * @method static Builder<static>|MemberInvitation whereSenderId($value)
 * @method static Builder<static>|MemberInvitation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class MemberInvitation extends Model
{
    use HasFactory, HasUlids, LivesInRbacDB;

    protected $fillable = [
        'role_key',
        'receiver_email',
        'expires_at',
        'accepted_at',
        'rejected_at',
    ];

    protected $with = [
        'sender',
        'tenant',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function expired(): bool
    {
        return $this->getAttribute('expires_at')->isPast();
    }

    public function accepted(): bool
    {
        return ! empty($this->getAttribute('accepted_at'));
    }

    public function rejected(): bool
    {
        return ! empty($this->getAttribute('rejected_at'));
    }

    #[Scope]
    protected function forTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', '=', $tenant->id);
    }

    #[Scope]
    protected function sentBy(Builder $query, User $user): Builder
    {
        return $query->where('sender_id', '=', $user->id);
    }

    protected function casts(): array
    {
        return [
            'role_key' => RoleCast::class,
            'accepted_at' => 'timestamp',
            'rejected_at' => 'timestamp',
            'expires_at' => 'timestamp',
        ];
    }
}
