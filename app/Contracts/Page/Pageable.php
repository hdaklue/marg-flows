<?php

declare(strict_types=1);

namespace App\Contracts\Page;

use App\Contracts\Role\RoleableEntity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface Pageable extends RoleableEntity
{
    public function pages(): MorphMany;

    public function getPages(): Collection;

    public function getKey();

    public function getMorphClass();
}
