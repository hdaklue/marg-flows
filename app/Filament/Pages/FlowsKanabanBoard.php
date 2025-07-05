<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Models\User;
use App\Services\Flow\TimeProgressService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class FlowsKanabanBoard extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static string $statusEnum = FlowStatus::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $title = 'Flows';

    public string $progressService = TimeProgressService::class;

    protected ?string $maxContentWidth = 'full';

    // protected $listeners = ['members-updated' => '$refresh'];

    public function getHeaderActions(): array
    {
        return [
        ];
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {

        $record = $this->getEloquentQuery()->find($recordId);

        match ((int) $status) {
            FlowStatus::COMPLETED->value => $record->setAsCompleted(),
            FlowStatus::CANCELED->value => $record->setAsCanceled(),
            default => $record->setStatus(FlowStatus::from((int) $status)),

        };

        if (method_exists(static::$model, 'setNewOrder')) {
            static::$model::setNewOrder($toOrderedIds);
        }
        $this->dispatch("board-item-updated.{$recordId}");
    }

    /**
     * @var User;
     */
    #[Computed]
    public function canManageFlow()
    {
        return filamentUser()->can('manageFlows', filamentTenant()) ?? false;
    }

    protected function records(): Collection
    {

        $isAdmin = filamentTenant()->isAdmin(filamentUser());

        return Flow::unless($isAdmin, function ($query) {
            $query->forParticipant(filamentUser());
        })->byTenant(filamentTenant())->with(['participants'])->ordered()->get();
    }

    protected function getProgressPercentage(Flow $record)
    {
        return app($this->progressService)->getProgressDetails($record)['percentage'];
    }
}
