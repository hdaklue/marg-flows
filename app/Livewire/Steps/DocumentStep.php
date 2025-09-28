<?php

declare(strict_types=1);

namespace App\Livewire\Steps;

use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Actions\Flow\EditFlowInfoAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Document;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use Hdaklue\Actioncrumb\Traits\HasActionCrumbs;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class DocumentStep extends Component implements HasActions, HasSchemas
{
    use HasActionCrumbs;
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Locked]
    public string $documentId;

    #[Locked]
    public string $stepType;

    public function mount($record = null, $stepType = 'flow'): void
    {
        $this->documentId = $record->id;
        $this->stepType = $stepType;
    }

    #[Computed]
    public function document(): Document
    {
        return Document::with('documentable')->findOrFail($this->documentId);
    }

    #[Computed]
    public function flow(): Flow
    {
        return $this->document->documentable;
    }

    public function deleteDocumentAction(): Action
    {
        return Action::make('deleteDocument')
            ->label('Delete Document')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->action(function () {
                Notification::make()
                    ->title('Delete Document')
                    ->body('Delete document functionality')
                    ->warning()
                    ->send();
            });
    }

    public function createDocumentAction(): Action
    {
        if (! $this->document) {
            throw new InvalidArgumentException('Document is required for createDocumentAction');
        }

        return CreateDocumentAction::make($this->document->documentable, shouldRedirect: true);
    }

    public function editFlowAction(): EditAction
    {
        return EditFlowInfoAction::make($this->flow);
    }

    public function render()
    {
        return view('livewire.steps.document-step');
    }

    protected function actioncrumbs(): array
    {
        if (! $this->document) {
            return [];
        }

        $flowDocsUrl = FlowResource::getUrl('view', [
            'tenant' => filamentTenant(),
            'record' => $this->flow,
            'activeTab' => 'documents',
        ]);
        $flowUrl = FlowResource::getUrl('view', [
            'tenant' => filamentTenant(),
            'record' => $this->flow,
        ]);

        if ($this->stepType === 'flow') {
            return [
                Step::make('flow')
                    ->label(fn () => str($this->flow->title)->title())
                    ->icon(FlowResource::getNavigationIcon())
                    ->url($flowUrl)
                    ->actions([
                        WireAction::make('Edit Flow')
                            ->livewire($this)
                            ->icon('heroicon-o-plus')
                            ->execute('editFlow'),
                    ]),
            ];
        }

        if ($this->stepType === 'document') {
            return [
                Step::make('document')
                    ->label('Documents')
                    ->url($flowDocsUrl)
                    ->icon('heroicon-o-folder')
                    ->actions([
                        WireAction::make('Create Document')
                            ->livewire($this)
                            ->icon('heroicon-o-plus')
                            ->execute('createDocument'),
                    ]),
            ];
        }

        if ($this->stepType === 'current') {
            return [
                Step::make($this->document->name)
                    ->current()
                    ->actions([
                        \Hdaklue\Actioncrumb\Action::make('share')
                            ->label('Share with ..')
                            ->visible(fn () => filamentUser()->can('manage', $this->document))
                            ->execute(fn () => $this->dispatch(
                                'open-modal',
                                id: 'manage-participants-modal',
                            )),
                    ]),
            ];
        }

        return [];
    }
}
