<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Filament\Resources\Flows\Actions\CreateFlowAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use Hdaklue\Actioncrumb\Traits\HasActionCrumbs;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class FlowStep extends Component implements HasActions, HasSchemas
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

    public function createFlowAction(): Action
    {
        return CreateFlowAction::make($this);
    }

    public function render()
    {
        return view('livewire.steps.flow-step');
    }

    protected function actioncrumbs(): array
    {
        return [
            Step::make('flows')
                ->label('Flows')
                ->icon(FlowResource::getNavigationIcon())
                ->actions([
                    WireAction::make('create_flow')
                        ->label('Create Flow')
                        ->livewire($this)
                        ->execute('createFlow'),
                ])
                ->url(fn () => FlowResource::getUrl('index', ['tenant' => filamentTenant()])),
        ];
    }
}
