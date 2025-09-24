<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\SideNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface Sidenoteable
{
    public function getKey();

    public function getMorphClass();

    public function sideNotes(): MorphMany;

    public function getSideNotes(): Collection;

    public function addSideNote(SideNote $sidenote): SideNote;

    public function getSideNotesBy(User $user): Collection;

    public function getSideNote(string|int $id): null|SideNote;

    public function deleteSideNote(int|string|SideNote $entity);
}
