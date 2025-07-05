<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class ViewFlow extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'task-kanban.kanban-board';

    protected static string $headerView = 'task-kanban.kanban-header';

    protected static string $statusView = 'task-kanban.kanban-status';

    protected static string $scriptsView = 'task-kanban.kanban-scripts';

    #[Url('record')]
    #[Locked]
    public string $recordId;

    #[Computed]
    public function flow(): Flow
    {
        return Flow::where('id', $this->recordId)->first();
    }

    #[Computed]
    public function canManageFlow(): bool
    {
        return filamentUser()->can('manageFlows', filamentTenant());
    }

    public function getHeading(): string|Htmlable
    {
        return $this->flow->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Knowledge')
                ->color('gray')
                ->outlined()
                ->url(FlowResource::getUrl('pages', ['record' => $this->recordId])),
        ];
    }

    protected function records(): Collection
    {

        $this->authorize('view', $this->flow);

        return collect([]);
    }

    protected function statuses(): Collection
    {

        return $this->flow->stages->map(fn ($item) => $item->toArray());
    }
}
