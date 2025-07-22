<?php

declare(strict_types=1);

namespace App\Contracts\Page;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface Pageable
{
    public function pages(): MorphMany;

    public function getPages(): Collection;

    public function getKey();

    public function getMorphClass();
}
