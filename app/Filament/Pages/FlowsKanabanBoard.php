<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\FlowStatus;
use App\Models\Flow;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class FlowsKanabanBoard extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static string $statusEnum = FlowStatus::class;

    protected static ?string $navigationLabel = 'Projects';

    protected ?string $maxContentWidth = 'full';

    protected function records(): Collection
    {
        $isAdmin = filament()->getTenant()->isAdmin(\filament()->auth()->user());

        return Flow::when(! $isAdmin, function ($query) {
            $query->forParticipant(\filament()->auth()->user());
        })->byTenant(filament()->getTenant())->with(['creator', 'participants'])->ordered()->orderBy('due_date')->get();
    }
}
