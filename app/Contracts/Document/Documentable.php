<?php

declare(strict_types=1);

namespace App\Contracts\Document;

use App\Contracts\Role\RoleableEntity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface Documentable extends RoleableEntity
{
    public function documents(): MorphMany;

    public function getDocuments(): Collection;

    public function getKey();

    public function getMorphClass();
}
