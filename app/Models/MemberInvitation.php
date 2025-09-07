<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
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
    use HasFactory, HasUlids, LivesInOriginalDB;

    protected $fillable = [
        'role_data',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    protected function scopeSentBy(Builder $query, User $user): Builder
    {
        return $query->where('sender_id', '=', $user->id);
    }

    protected function casts(): array
    {
        return [
            'role_data' => 'array',
        ];
    }
}
