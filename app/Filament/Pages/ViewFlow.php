<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

/**
 * ViewFlow
 */
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

    protected ?string $maxContentWidth = 'full';

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
            Action::make('add')
                ->label('Task')
                ->color('primary')
                ->size(ActionSize::ExtraSmall)
                ->icon('heroicon-o-plus-circle')
                ->outlined()
                ->url(FlowResource::getUrl('pages', ['record' => $this->recordId])),
            Action::make('view')
                ->label('Knowledge')
                ->color('gray')
                ->size(ActionSize::ExtraSmall)
                ->icon('heroicon-o-document-text')
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
