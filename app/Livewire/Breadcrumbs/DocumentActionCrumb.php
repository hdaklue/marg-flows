<?php

declare(strict_types=1);

namespace App\Livewire\Breadcrumbs;

use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Actions\Flow\EditFlowInfoAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Document;
use App\Models\Flow;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Hdaklue\Actioncrumb\Components\WireCrumb;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Document-specific breadcrumb component with actions.
 */
final class DocumentActionCrumb extends WireCrumb
{
    #[Locked]
    public string $documentId;

    public function mount($record = null, $parent = null)
    {
        $this->documentId = $record->id;
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

    public function render()
    {
        return view('livewire.breadcrumbs.document-action-crumb', [
            'renderedActioncrumbs' => $this->renderActioncrumbs(),
        ]);
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

        return [
            Step::make($this->flow->title)
                ->label(fn () => $this->flow->title)
                ->icon(FlowResource::getNavigationIcon())
                ->url($flowUrl)
                ->actions([
                    WireAction::make('Edit Flow')
                        ->livewire($this)
                        ->icon('heroicon-o-plus')
                        ->execute('editFlow'),
                ]),
            Step::make('Documents')
                ->url($flowDocsUrl)
                ->icon('heroicon-o-folder')
                ->actions([
                    WireAction::make('Create Document')
                        ->livewire($this)
                        ->icon('heroicon-o-plus')
                        ->execute('createDocument'),
                ]),
            Step::make($this->document->name)
                ->current()
                ->actions([
                    // WireAction for create document
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
}
