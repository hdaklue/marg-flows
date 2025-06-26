<?php

declare(strict_types=1);

namespace App\Livewire\Kanban;

use App\Enums\FlowStatus;
use App\Models\Flow;
use Livewire\Attributes\On;
use Livewire\Component;

class Record extends Component
{
    public Flow $record;

    public string $color;

    protected $listeners = ['members-updated' => '$refresh'];

    public function mount()
    {
        $this->color = $this->getColor();
    }

    public function getColor()
    {
        return FlowStatus::from($this->record->status)->getColor();
    }

    #[On('status-changed')]
    public function reloadColors()
    {
        $this->color = $this->getColor();
    }

    public function render()
    {
        return view('livewire.kanban.record');
    }
}
