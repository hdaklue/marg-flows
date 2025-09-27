<?php

declare(strict_types=1);

namespace App\Livewire\Breadcrumbs;

use App\Filament\Resources\Flows\Actions\CreateFlowAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Hdaklue\Actioncrumb\Action;
use Hdaklue\Actioncrumb\Components\WireCrumb;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

final class FlowActionCrumb extends WireCrumb
{
    #[Locked]
    public string $flowId;

    public function mount($record = null, $parent = null): void
    {
        // parent::mount($record, $parent);
        $this->flowId = $record->id;
    }

    public function createFlowAction(): \Filament\Actions\Action
    {
        return CreateFlowAction::make($this);
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
                ->url(fn () => FlowResource::getUrl('index', ['teanant' => filamentTenant()])),
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
