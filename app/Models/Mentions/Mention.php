<?php

declare(strict_types=1);

namespace App\Models\Mentions;

use App\Concerns\Database\LivesInOriginalDB;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Mention extends Model
{
    use HasUlids, LivesInOriginalDB;

    protected $with = ['mentionable', 'actor', 'mentioned'];

    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function mentioned(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'mentioned_id');
    }
}
