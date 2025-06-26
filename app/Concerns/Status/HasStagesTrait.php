<?php

declare(strict_types=1);

namespace App\Concerns\Status;

use App\Enums\FlowStatus;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStagesTrait
{
    public function stages(): MorphMany
    {
        return $this->morphMany(Stage::class, 'statuable');
    }

    public function scopeByStage(Builder $builder, string|FlowStatus $status): Builder
    {
        $status = $status instanceof FlowStatus ? $status->value : $status;

        return $builder->whereHas('stages', function ($query) use ($status) {
            $query->where('name', $status);
        });
    }
}
