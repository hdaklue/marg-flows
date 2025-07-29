<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Acknowledgement extends Model
{
    use HasUlids, LivesInBusinessDB;

    public function acknowledgeable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
