<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use App\Contracts\Sidenoteable;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $content
 * @property string $sidenoteable_type
 * @property string $sidenoteable_id
 * @property string $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $creator
 * @property-read Model|Eloquent $sidenoteable
 *
 * @method static Builder<static>|SideNote newModelQuery()
 * @method static Builder<static>|SideNote newQuery()
 * @method static Builder<static>|SideNote query()
 * @method static Builder<static>|SideNote whereContent($value)
 * @method static Builder<static>|SideNote whereCreatedAt($value)
 * @method static Builder<static>|SideNote whereId($value)
 * @method static Builder<static>|SideNote whereOwnerId($value)
 * @method static Builder<static>|SideNote whereSidenoteableId($value)
 * @method static Builder<static>|SideNote whereSidenoteableType($value)
 * @method static Builder<static>|SideNote whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 * @mixin Eloquent
 */
final class SideNote extends Model
{
    use HasUlids, LivesInOriginalDB;

    public $fillable = [
        'content',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sidenoteable(): MorphTo
    {
        return $this->morphTo();
    }

    #[Scope]
    protected function for(Builder $builder, Sidenoteable $entity, User $owner): Builder
    {
        return $builder->where('owner_id', $owner->getKey())
            ->whereHas('sidenoteable', function ($query) use ($entity) {
                $query->where('sidenoteable_id', $entity->getKey())
                    ->where('sidenoteable_type', $entity->getMorphClass());
            });
    }
}
