<?php

declare(strict_types=1);

namespace App\Livewire\Breadcrumbs;

use App\Filament\Actions\Flow\EditFlowInfoAction;
use App\Filament\Resources\Flows\Actions\CreateFlowAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Hdaklue\Actioncrumb\Components\WireCrumb;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use Hdaklue\Actioncrumb\Traits\HasActionCrumbs;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Throwable;

final class FlowActionCrumb extends WireCrumb implements HasActions, HasSchemas
{
    use HasActionCrumbs,
        InteractsWithActions,
        InteractsWithSchemas;

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

    public function createFlowAction(): Action
    {
        return CreateFlowAction::make($this);
    }

    public function render()
    {
        return view('livewire.breadcrumbs.flow-action-crumb');
    }

    public function editFlowAction(): EditAction
    {
        return EditFlowInfoAction::make($this->flow);
    }

    /**
     * @throws Throwable
     */
    protected function actioncrumbs(): array
    {
        $flowUrl = FlowResource::getUrl('index', ['tenant' => filamentTenant()]);

        return [
            Step::make('flows')
                ->label('Flows')
                ->icon(FlowResource::getNavigationIcon())
                ->url($flowUrl)
                ->actions([WireAction::make('create_flow')
                    ->label('Create Flow')
                    ->visible(fn () => filamentUser()->can('create', [Flow::class, filamentTenant()]))
                    ->livewire($this)
                    ->execute('createFlow'), ]),

            Step::make('current')
                ->label(fn () => str($this->flow->getAttribute('title'))->title())
                ->actions([
                    WireAction::make('Edit Info')
                        ->livewire($this)
                        ->icon('heroicon-o-plus')
                        ->execute('editFlow'),
                ])
                ->current(),
        ];
    }
}
