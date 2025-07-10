<?php

declare(strict_types=1);

namespace App\Livewire\Kanban;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Services\Flow\TimeProgressService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Summary of Record.
 *
 * @property-read Collection $participants
 */
final class Record extends Component
{
    #[Locked]
    public Flow $record;

    public string $color;

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
        $this->color = cache()->remember(
            "flow_color_{$this->record->status}",
            3600,
            fn () => FlowStatus::from($this->record->status)->getColor(),
        );
        unset($this->participants);
        $this->shouldShowProgressDetails = in_array($this->record->status, [FlowStatus::ACTIVE->value, FlowStatus::SCHEDULED->value]);

        if ($this->shouldShowProgressDetails) {
            $this->progressDetails = app(TimeProgressService::class)->getProgressDetails($this->record);
        }
    }

    #[Computed]
    public function participants(): Collection
    {
        return $this->record->getParticipants();
    }

    #[Computed(true)]
    public function userPermissions(): array
    {
        return [
            'canManageFlows' => filamentUser()->can('manageFlows', filamentTenant()),
            'canManageMembers' => filamentUser()->can('manageMembers', $this->record),
        ];
    }

    public function render()
    {
        return view('livewire.kanban.record');
    }
}
