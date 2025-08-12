<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $ip_address
 * @property string $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereUserId($value)
 * @mixin \Eloquent
 */
final class LoginLog extends Model
{
    use HasUlids, LivesInOriginalDB;

    protected $fillable = [
        'user_agent',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
