<?php

declare(strict_types=1);

namespace App\Livewire\Kanban;

use App\Enums\FlowStatus;
use App\Models\Flow;
use App\Services\Flow\TimeProgressService;
use Livewire\Attributes\On;
use Livewire\Component;

class Record extends Component
{
    public Flow $record;

    public string $color;

    public array $progressDetails;

    public bool $shouldShowProgressDetails;

    protected $listeners = ['members-updated' => '$refresh'];

    public function mount()
    {
        $this->setColor();
        $this->setProgressDetails();
        $this->setShouldShowProgressDetails();
    }

    public function setColor()
    {
        $this->color = FlowStatus::from($this->record->status)->getColor();
    }

    public function shouldShowProgressDetails()
    {

        return $this->record->status == FlowStatus::ACTIVE->value || $this->record->status == FlowStatus::SCHEDULED->value;
    }

    public function setShouldShowProgressDetails()
    {

        $this->shouldShowProgressDetails = $this->shouldShowProgressDetails();
    }

    public function setProgressDetails()
    {
        $this->progressDetails = app(TimeProgressService::class)->getProgressDetails($this->record);
    }

    public function setColors()
    {
        $this->color = $this->getColor();
    }

    #[On('status-changed')]
    public function handleStatusChange()
    {

        $this->setColor();
        $this->setShouldShowProgressDetails();
        $this->setProgressDetails();

    }

    public function render()
    {
        return view('livewire.kanban.record');
    }
}
