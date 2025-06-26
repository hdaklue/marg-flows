<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Services\Flow\FlowProgressService;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class FlowsKanabanBoard extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static string $statusEnum = FlowStatus::class;

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $title = 'Flows';

    public string $progressService = FlowProgressService::class;

    protected ?string $maxContentWidth = 'full';

    // public function onSortChanged(int|string $recordId, string $status, array $orderedIds): void
    // {
    //     $newOrder = collect($orderedIds)->sort()->toArray();
    //     if (method_exists(static::$model, 'setNewOrder')) {
    //         static::$model::setNewOrder($newOrder);
    //     }
    // }

    protected function records(): Collection
    {
        $isAdmin = filament()->getTenant()->isAdmin(\filament()->auth()->user());

        return Flow::when(! $isAdmin, function ($query) {
            $query->forParticipant(\filament()->auth()->user());
        })->byTenant(filament()->getTenant())->with(['creator', 'participants'])->ordered()->get();
    }

    protected function getProgressPercentage(Flow $record)
    {
        return app($this->progressService)->getProgressDetails($record)['percentage'];
    }
}
