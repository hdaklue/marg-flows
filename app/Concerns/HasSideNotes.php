<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\SideNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSideNotes
{
    public function sideNotes(): MorphMany
    {
        return $this->morphMany(SideNote::class, 'sidenoteable');
    }

    public function sideNotesFor(User $user): Collection
    {
        return $this->sideNotes()->whereBelongsTo($user,'creator')->get();
    }

    public function hasSideNotesFor(User $user): bool
    {
        return $this->sideNotes()->whereBelongsTo($user,'creator')->exists();
    }
}
