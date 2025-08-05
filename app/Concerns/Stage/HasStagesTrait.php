<?php

declare(strict_types=1);

namespace App\Concerns\Stage;

use App\Enums\FlowStage;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStagesTrait
{
    public function stages(): MorphMany
    {
        return $this->morphMany(Stage::class, 'stageable');
    }

    public function scopeByStage(Builder $builder, string|FlowStage $status): Builder
    {
        $status = $status instanceof FlowStage ? $status->value : $status;

        return $builder->whereHas('stages', function ($query) use ($status) {
            $query->where('name', $status);
        });
    }
}
