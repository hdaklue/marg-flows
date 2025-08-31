<?php

declare(strict_types=1);

namespace App\Contracts\Stage;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasStages
{
    public function stages(): MorphMany;

    public function getKey();

    public function getMorphClass();
}
