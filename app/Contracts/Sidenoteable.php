<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\Models\SideNote;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Sidenoteable
{
    public function getKey();

    public function getMorphClass();

    public function sideNotes(): MorphMany;

    public function getSideNotes(): Collection;

    public function addSideNote(SideNote $sidenote): SideNote;

    public function getSideNotesBy(User $user): Collection;

    public function getSideNote(string|int $id): ?SideNote;

    public function deleteSideNote(int|string|SideNote $entity);
}
