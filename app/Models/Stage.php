<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Stage\HasStages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
