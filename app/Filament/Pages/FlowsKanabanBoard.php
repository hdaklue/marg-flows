<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Services\Flow\FlowProgressService;
use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class FlowsKanabanBoard extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static string $statusEnum = FlowStatus::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $title = 'Flows';

    public string $progressService = FlowProgressService::class;

    protected ?string $maxContentWidth = 'full';

    protected $listeners = ['members-updated' => '$refresh'];

    public function getHeaderActions(): array
    {
        return [
            //         Action::make('Create')
            //   ,
        ];
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {

        $this->getEloquentQuery()->find($recordId)->update([
            static::$recordStatusAttribute => $status,
            'completed_at' => FlowStatus::from((int) $status)->value === FlowStatus::COMPLETED->value ? now() : null,
        ]);

        if (method_exists(static::$model, 'setNewOrder')) {
            static::$model::setNewOrder($toOrderedIds);
        }
    }

    protected function records(): Collection
    {

        $isAdmin = filament()->getTenant()->isAdmin(\filament()->auth()->user());

        return Flow::unless($isAdmin, function ($query) {
            $query->forParticipant(\filament()->auth()->user());
        })->byTenant(filament()->getTenant())->with(['creator', 'participants'])->ordered()->get();
    }

    protected function getProgressPercentage(Flow $record)
    {
        return app($this->progressService)->getProgressDetails($record)['percentage'];
    }
}
