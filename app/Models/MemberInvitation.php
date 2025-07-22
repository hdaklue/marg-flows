<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property string $id
 * @property string $sender_id
 * @property string $receiver_id
 * @property array<array-key, mixed> $role_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $receiver
 * @property-read \App\Models\User $sender
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
 * @mixin IdeHelperMemberInvitation
 * @mixin \Eloquent
 */
class MemberInvitation extends Model
{
    use HasFactory, HasUlids;

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

    public function scopeSentBy(Builder $query, User $user): Builder
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
