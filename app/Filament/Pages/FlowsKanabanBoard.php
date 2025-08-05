<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\FlowStage;
use App\Models\Flow;
use App\Services\Flow\TimeProgressService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

final class FlowsKanabanBoard extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static string $statusEnum = FlowStage::class;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $title = 'Flows';

    protected static string $recordStatusAttribute = 'stage';

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

        /**
         * @var Flow $record
         */
        $record = $this->getEloquentQuery()->find($recordId);

        match ((int) $status) {
            FlowStage::COMPLETED->value => $record->setAsCompleted(),
            FlowStage::CANCELED->value => $record->setAsCanceled(),
            default => $record->setStatus(FlowStage::from((int) $status)),

        };

        if (method_exists(self::$model, 'setNewOrder')) {
            self::$model::setNewOrder($toOrderedIds);
        }
        $this->dispatch("board-item-updated.{$recordId}");
    }

    #[Computed]
    public function canManageFlow(): bool
    {
        return filamentUser()->can('manageFlows', filamentTenant());
    }

    protected function records(): Collection
    {

        $isAdmin = filamentTenant()->isAdmin(filamentUser());

        return Flow::unless($isAdmin, function ($query) {
            $query->forParticipant(filamentUser());
        })->byTenant(filamentTenant())->get();
    }

    protected function getProgressPercentage(Flow $record)
    {
        return app($this->progressService)->getProgressDetails($record)['percentage'];
    }
}
