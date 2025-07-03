<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Flow;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class ViewFlow extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'task-kanban.board';

    protected static string $headerView = 'task-kanban.header';

    protected static string $statusView = 'task-kanban.status';

    protected static string $scriptsView = 'task-kanban.scripts';

    #[Url('record')]
    public string $recordId;

    protected function records(): Collection
    {

        $this->authorize('view', Flow::where('id', $this->recordId)->first());

        return collect([]);
    }

    protected function statuses(): Collection
    {

        return collect([
            ['id' => '1', 'title' => 'User'],
            ['id' => '2', 'title' => 'Admin'],
        ]);
    }
}
