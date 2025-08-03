<?php

declare(strict_types=1);

namespace App\Contracts\Mentions;

use App\Models\Mentions\Mention;
use App\Services\MentionService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasMentions
{
    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function getMentionables(): Collection
    {
        return new MentionService()->getMentionables($this);
    }
}
