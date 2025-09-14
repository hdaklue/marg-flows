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

    public function getSideNotesBy(User $user): Collection
    {
        return $this->sideNotes()
            ->whereBelongsTo($user, 'creator')
            ->latest()
            ->get();
    }

    public function hasSideNotesBy(User $user): bool
    {
        return $this->sideNotes()->whereBelongsTo($user, 'creator')->exists();
    }

    public function getSideNotes(): Collection
    {
        return $this->sideNotes()->latest()->get();
    }

    public function addSideNote(SideNote $sidenote): SideNote
    {
        $this->sideNotes()->save($sidenote);

        /** @var SideNote */
        return $sidenote;
    }

    public function getSideNote(int|string $id): ?SideNote
    {
        /** @var SideNote|null */
        return $this->sideNotes()->whereKey($id)->first();
    }

    public function deleteSideNote(string|int|SideNote $entity)
    {
        $id = $entity instanceof SideNote ? $entity->getKey() : $entity;
        $this->sideNotes()->whereKey($id)->delete();
    }
}
