<?php

declare(strict_types=1);

namespace App\Contracts\Mentions;

use App\Facades\MentionService;
use App\Models\Mentions\Mention;
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
        return MentionService::getMentionables($this);
    }
}
