<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Concerns\Filament\Pages\DisableBreadcrumb;
use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Flows\FlowResource;
use App\Livewire\Flow\FlowTabs;
use App\Models\Flow;
use App\Services\Document\Actions\CreateDocument;
use App\Services\Document\Facades\DocumentTemplate;
use App\Services\Recency\Actions\RecordRecency;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Hdaklue\Actioncrumb\Traits\HasActionCrumbs;
use Hdaklue\Actioncrumb\ValueObjects\Action as CrumbAction;
use Hdaklue\Actioncrumb\ValueObjects\Step;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * @property-read Collection $stages
 */
final class ViewFlow extends ViewRecord
{
    use DisableBreadcrumb, HasActionCrumbs;

    protected static string $resource = FlowResource::class;

    // protected string $view = 'filament.resources.flow-resource.pages.view-flow';

    protected array $sortableRules = [
        'items' => ['required', 'array', 'max:50'],
        'items.*' => ['required', 'string'],
    ];

    public function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('add_stream')
                    ->label(__('flow.actions.stream'))
                    ->size(Size::ExtraSmall),
                // Action::make('add_document')
                //     ->label('Add Document')
                //     ->form([
                //         TextInput::make('name')->required()->maxLength(100),
                //         Select::make(
                //             'template',
                //         )->options(DocumentTemplate::templatesAsSelectArray()),
                //     ])
                //     ->action(function (array $data) {
                //         try {
                //             $driver = (string) $data['template'];

                //             $template = DocumentTemplate::$driver();
                //             $createdDocument = CreateDocument::run(
                //                 $data['name'],
                //                 filamentUser(),
                //                 $this->record,
                //                 $template,
                //             );
                //             $this->redirect(DocumentResource::getUrl('view', [
                //                 'record' => $createdDocument->getKey(),
                //             ]), true);

                //             // Notification::make()
                //             //     ->body(__('common.messages.operation_completed'))
                //             //     ->success()
                //             //     ->send();
                //         } catch (Exception $e) {
                //             logger()->error($e->getMessage());
                //             Notification::make()
                //                 ->body(__('common.messages.operation_failed'))
                //                 ->danger()
                //                 ->send();
                //         }
                //     }),
                CreateDocumentAction::make($this->record),
                Action::make('add_check')
                    ->label(__('flow.actions.checkpoint'))
                    ->size(Size::ExtraSmall),
            ])
                ->dropdownPlacement('bottom-start')
                ->label(__('flow.actions.create'))
                ->icon('heroicon-m-plus')
                ->outlined()
                ->size(Size::ExtraSmall)
                ->color('primary')
                ->button(),
            ActionGroup::make([
                // Action::make('view'),
                EditAction::make('edit')
                    ->visible(filamentUser()->can('update', $this->record))
                    ->record($this->record)
                    ->fillForm([
                        'title' => $this->record->getAttribute('title'),
                        'description' => $this->record->getAttribute('description'),
                    ])
                    ->schema([
                        TextInput::make('title'),
                        Textarea::make('description'),
                    ]),
                // Action::make('delete'),
            ])->dropdownPlacement('bottom-end'),
        ];
    }

    #[Computed]
    public function getStages(): Collection
    {
        return $this->record->stages;
    }

    // public function onSort(array $itemIds, ?string $from = null, ?string $to = null): mixed
    // {
    //     return true;
    // }

    public function content(Schema $schema): Schema
    {
        RecordRecency::dispatch(filamentUser(), $this->record);

        return $schema->components([
            Livewire::make(FlowTabs::class, ['flowId' => $this->record->getKey()]),
        ]);
    }

    // #[On('sortable:sort')]
    // public function updateSort($payload)
    // {

    //     $itemIds = $args[0] ?? [];
    //     $eventData = $args[1] ?? null;

    //     logger()->info('updateSort called', [
    //         'itemIds' => $payload['items'],
    //         'eventData' => $eventData,
    //         // 'args_count' => count($args),
    //     ]);

    //     try {
    //         $this->handleSort($itemIds, $eventData);
    //     } catch (Exception $e) {
    //         logger()->error('updateSort failed', [
    //             'error' => $e->getMessage(),
    //             'itemIds' => $itemIds,
    //             'eventData' => $eventData,
    //         ]);
    //         $this->addError('sort', 'Failed to update sort order: ' . $e->getMessage());
    //     }
    // }
    // public function hasResourceBreadcrumbs(): bool
    // {
    //     return true;
    // }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->description ? ucfirst($this->record->description) : '';
    }

    public function getTitle(): string|Htmlable
    {
        return ucfirst($this->record->title);
    }

    protected function actioncrumbs(): array
    {
        return [
            Step::make('Dashboard')->icon('heroicon-o-home')->url('/dashboard'),
            Step::make('Users')
                ->icon('heroicon-o-users')
                ->current()
                ->actions([
                    CrumbAction::make('Export Users')->icon('heroicon-o-arrow-down-tray')->url('/'),
                    CrumbAction::make('Import Users')->icon('heroicon-o-arrow-up-tray')->url('/'),
                    CrumbAction::make('User Settings')->icon('heroicon-o-cog-6-tooth')->url(
                        '/admin/users/settings',
                    ),
                ]),
        ];
    }
}
