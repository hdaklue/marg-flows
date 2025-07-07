<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sidenoteable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SideNote extends Model
{
    use HasUlids;

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

    public function scopeFor(Builder $builder, Sidenoteable $entity, User $owner): Builder
    {
        return $builder->where('owner_id', $owner->getKey())
            ->whereHas('sidenoteable', function ($query) use ($entity) {
                $query->where('sidenoteable_id', $entity->getKey())
                    ->where('sidenoteable_type', $entity->getMorphClass());
            });
    }
}
