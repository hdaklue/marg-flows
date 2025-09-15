<?php

declare(strict_types=1);

namespace App\Livewire\Breadcrumbs;

use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Hdaklue\Actioncrumb\Components\WireCrumb;
use Hdaklue\Actioncrumb\Step;
use Hdaklue\Actioncrumb\Support\WireAction;
use InvalidArgumentException;
use Livewire\Attributes\Locked;

/**
 * Document-specific breadcrumb component with actions.
 */
final class DocumentActionCrumb extends WireCrumb
{
    #[Locked]
    public null|Document $document = null;

    public function mount($record = null, $parent = null)
    {
        parent::mount($record, $parent);
        $this->document = $record;
    }

    public function render()
    {
        return view('livewire.breadcrumbs.document-action-crumb');
    }

    // Common actions that breadcrumb factories can use
    public function testAction(): Action
    {
        return Action::make('test')
            ->label('Test Action')
            ->icon('heroicon-o-bell')
            ->action(function () {
                Notification::make()
                    ->title('Test Action Executed!')
                    ->body('This action was executed from the DocumentActionCrumb component')
                    ->success()
                    ->send();
            });
    }

    public function editDocumentAction(): Action
    {
        return Action::make('editDocument')
            ->label('Edit Document')
            ->icon('heroicon-o-pencil')
            ->action(function () {
                Notification::make()
                    ->title('Edit Document')
                    ->body('Edit document functionality')
                    ->info()
                    ->send();
            });
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
        if (!$this->document) {
            throw new InvalidArgumentException('Document is required for createDocumentAction');
        }

        return CreateDocumentAction::make($this->document->documentable, shouldRedirect: true);
    }

    protected function actioncrumbs(): array
    {
        if (!$this->document) {
            return [];
        }

        $flow = $this->document->loadMissing('documentable')->documentable;
        $flowUrl = FlowResource::getUrl('view', [
            'tenant' => filamentTenant(),
            'record' => $flow,
        ]);

        $flowDocsUrl = FlowResource::getUrl('view', [
            'tenant' => filamentTenant(),
            'record' => $flow,
            'activeTab' => 'documents',
        ]);

        // Simple test steps first
        return [
            Step::make($flow->title)->icon(FlowResource::getNavigationIcon())->url($flowUrl),
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
                    // Regular actioncrumb action
                    \Hdaklue\Actioncrumb\Action::make('Direct Action')
                        ->icon('heroicon-o-bolt')
                        ->execute(function () {
                            Notification::make()
                                ->title('Direct Action from DocumentActionCrumb!')
                                ->success()
                                ->send();
                        }),
                    // WireAction that calls this component's methods
                    WireAction::make('Wire Action')
                        ->livewire($this)
                        ->icon('heroicon-o-bell')
                        ->execute('test'),
                    // WireAction for create document
                ]),
        ];
    }
}
