<?php

declare(strict_types=1);

namespace App\Concerns\Mentions;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface HasMentionsContract
{
    public function mentions(): MorphMany;

    public function getMentions(): Collection;
}
