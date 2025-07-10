<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Stage\HasStages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property string $stageable_type
 * @property string $stageable_id
 * @property string $color
 * @property array<array-key, mixed>|null $settings
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $stageable
 * @method static Builder<static>|Stage by(\App\Contracts\Stage\HasStages $entity)
 * @method static Builder<static>|Stage newModelQuery()
 * @method static Builder<static>|Stage newQuery()
 * @method static Builder<static>|Stage query()
 * @method static Builder<static>|Stage whereColor($value)
 * @method static Builder<static>|Stage whereCreatedAt($value)
 * @method static Builder<static>|Stage whereId($value)
 * @method static Builder<static>|Stage whereName($value)
 * @method static Builder<static>|Stage whereOrder($value)
 * @method static Builder<static>|Stage whereSettings($value)
 * @method static Builder<static>|Stage whereStageableId($value)
 * @method static Builder<static>|Stage whereStageableType($value)
 * @method static Builder<static>|Stage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Stage extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'color',
        'order',
        'settings',
        'tenant_id',
    ];

    public function stageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeBy(Builder $builder, HasStages $entity)
    {
        return $builder->whereHas('stageable', function ($query) use ($entity) {
            $query->where('stageable_id', $entity->getKey())
                ->where('stageable_type', $entity->getMorphClass());
        });
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
