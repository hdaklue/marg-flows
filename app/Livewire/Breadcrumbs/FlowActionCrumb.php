<?php

declare(strict_types=1);

namespace App\Livewire\Breadcrumbs;

use App\Livewire\Steps\CurrentFlowStep;
use App\Livewire\Steps\FlowStep;
use App\Models\Flow;
use Hdaklue\Actioncrumb\Components\WireCrumb;
use Hdaklue\Actioncrumb\Components\WireStep;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

final class FlowActionCrumb extends WireCrumb
{
    #[Locked]
    public string $flowId;

    public function mount($record = null, $parent = null): void
    {
        parent::mount($record, $parent);
        $this->flowId = $record->id;
    }

    #[Computed]
    public function flow()
    {
        return Flow::whereKey($this->flowId)->first();
    }

    public function render()
    {
        return view('livewire.breadcrumbs.flow-action-crumb');
    }

    protected function crumbSteps(): array
    {
        return [
            WireStep::make(FlowStep::class, ['record' => $this->flow])
                ->stepId('flows'),
            WireStep::make(CurrentFlowStep::class, ['record' => $this->flow])
                ->stepId('current'),
        ];
    }
}
