<?php

declare(strict_types=1);

namespace App\Livewire\Kanban;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Services\Flow\TimeProgressService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Record extends Component
{
    public Flow $record;

    public string $color = '';

    public array $progressDetails = [];

    public bool $shouldShowProgressDetails = false;

    protected $listeners = ['board-item-updated.{record.id}' => '$refresh'];

    public function mount()
    {
        $this->refreshComputedData();
    }

    #[On('board-item-updated.{record.id}')]
    public function refreshComputedData()
    {
        $this->color = FlowStatus::from($this->record->status)->getColor();
        $this->shouldShowProgressDetails = in_array($this->record->status, [FlowStatus::ACTIVE->value, FlowStatus::SCHEDULED->value]);

        if ($this->shouldShowProgressDetails) {
            $this->progressDetails = app(TimeProgressService::class)->getProgressDetails($this->record);
        }
    }

    #[Computed]
    public function userPermissions(): array
    {
        return [
            'canManageFlows' => auth()->user()->can('manageFlows', filament()->getTenant()),
            'canManageMembers' => auth()->user()->can('manageMembers', $this->record),
        ];
    }

    public function render()
    {
        return view('livewire.kanban.record');
    }
}
