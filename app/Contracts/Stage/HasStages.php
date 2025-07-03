<?php

declare(strict_types=1);

namespace App\Contracts\Stage;

use App\Enums\FlowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasStages
{
    public function stages(): MorphMany;

    public function scopeByStage(Builder $builder, string|FlowStatus $status);
}
