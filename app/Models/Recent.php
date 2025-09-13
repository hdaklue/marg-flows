<?php

namespace App\Models;

use App\Concerns\Database\LivesInOriginalDB;
use App\Services\Recency\Contracts\Recentable;
use App\Services\Recency\RecentableCollection;
use Hdaklue\MargRbac\Concerns\Tenant\BelongsToTenant;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[CollectedBy(RecentableCollection::class)]
class Recent extends Model
{
    use LivesInOriginalDB, BelongsToTenant;

    protected $with = ['recentable'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interacted_at' => 'timestamp',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recentable(): MorphTo
    {
        return $this->morphTo('recentable');
    }

    #[Scope]
    protected function forUser(Builder $builder, Authenticatable $authenticatable)
    {
        return $builder->whereBelongsTo($authenticatable);
    }

    #[Scope]
    protected function forRecentable(Builder $builder, Recentable $recentable)
    {
        return $builder->whereHasMorph(
            'recentable',
            $recentable->getRecentType(),
            fn(Builder $q) => $q->whereKey($recentable->getRecentKey()),
        );
    }
}
