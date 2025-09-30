<?php

declare(strict_types=1);

namespace App\Livewire\Breadcrumbs\Steps;

use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use Livewire\Component;
use Livewire\Livewire;

/**
 * Reusable DocumentStep Livewire component for ActionCrumb breadcrumbs.
 *
 * This component is self-contained and manages its own actions.
 *
 * Usage:
 * DocumentStep::step($flowId)
 */
final class DocumentStep extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $flowId;

    public ?string $customLabel = null;

    public ?string $customIcon = null;

    /**
     * Static method to create a Step configured with this component's actions.
     * Returns a Step that will use JavaScript to trigger the action on the rendered component.
     */
    public static function step(string $flowId, ?string $label = null, ?string $icon = null): Step
    {
        $flow = Flow::findOrFail($flowId);

        $flowDocsUrl = FlowResource::getUrl('view', [
            'tenant' => filamentTenant(),
            'record' => $flow,
            'activeTab' => 'documents',
        ]);

        return Step::make('document')
            ->label($label ?? 'Documents')
            ->icon($icon ?? 'heroicon-o-folder')
            ->url($flowDocsUrl)
            ->actions([
                WireAction::make('Create Document')
                    ->livewire($instance)
                    ->icon('heroicon-o-plus')
                    ->execute('createDocument'),
            ]);
    }

    public function createDocumentAction(): Action
    {
        $flow = Flow::findOrFail($this->flowId);

        return CreateDocumentAction::make($flow, shouldRedirect: true);
    }

    public function render()
    {
        return view('livewire.breadcrumbs.steps.document-step');
    }
}
