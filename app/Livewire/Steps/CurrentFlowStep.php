<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Models\Flow;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Hdaklue\Actioncrumb\Action;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Traits\HasActionCrumbs;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class CurrentFlowStep extends Component implements HasActions, HasSchemas
{
    use HasActionCrumbs;
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Locked]
    public string $flowId;

    public function mount($record = null): void
    {
        $this->flowId = $record->id;
    }

    #[Computed]
    public function flow(): Flow
    {
        return Flow::whereKey($this->flowId)->first();
    }

    public function render()
    {
        return view('livewire.steps.current-flow-step');
    }

    protected function actioncrumbs(): array
    {
        return [
            Step::make('current')
                ->label(fn () => str($this->flow->getAttribute('title'))->title())
                ->actions([
                    Action::make('share')
                        ->label('Share with')
                        ->visible(fn () => filamentUser()->can('manage', $this->flow))
                        ->execute(fn () => $this->dispatch(
                            'open-modal',
                            id: 'manage-participants-modal',
                        )),
                ])
                ->current(),
        ];
    }
}
