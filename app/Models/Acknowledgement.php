<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInBusinessDB;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $acknowledgeable_type
 * @property string $acknowledgeable_id
 * @property string $actor_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $acknowledgeable
 * @property-read \App\Models\User|null $actor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement whereAcknowledgeableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement whereAcknowledgeableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Acknowledgement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
