<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Concerns\Filament\Pages\DisableBreadcrumb;
use App\Filament\Resources\Flows\FlowResource;
use App\Livewire\Flow\FlowDocumentsTable;
use App\Livewire\Role\ManageMemebersTable;
use App\Livewire\SortableDemo;
use App\Models\Flow;
use App\Services\Document\Actions\CreateDocument;
use App\Services\Document\Facades\DocumentTemplate;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Nette\Schema\Expect;

/**
 * @property-read Collection $stages
 */
final class ViewFlow extends ViewRecord
{
    use DisableBreadcrumb;

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
                    ->label('Stream')
                    ->size(Size::ExtraSmall),
                Action::make('add_document')
                    ->label('Add Document')
                    ->form([
                        TextInput::make('name')->required()->maxLength(100),
                        Select::make(
                            'template',
                        )->options(DocumentTemplate::templatesAsSelectArray()),
                    ])
                    ->action(function (array $data) {
                        try {
                            $driver = (string) $data['template'];

                            $template = DocumentTemplate::$driver();
                            CreateDocument::run(
                                $data['name'],
                                filamentUser(),
                                $this->record,
                                $template,
                            );
                            Notification::make()
                                ->body(__(
                                    'common.messages.operation_completed',
                                ))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            logger()->error($e->getMessage());
                            Notification::make()
                                ->body(__('common.messages.operation_failed'))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('add_check')
                    ->label('Checkpoint')
                    ->size(Size::ExtraSmall),
            ])
                ->label('Create')
                ->icon('heroicon-m-plus')
                ->outlined()
                ->size(Size::ExtraSmall)
                ->color('primary')
                ->button(),
            ActionGroup::make([
                // Action::make('view'),
                Action::make('edit')
                    ->visible(filamentUser()->can('update', $this->record))
                    ->record($this->record)
                    ->schema([
                        TextInput::make('title'),
                        Textarea::make('description'),
                    ]),
                // Action::make('delete'),
            ]),
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
        return $schema->components([
            // Livewire::make(SortableDemo::class),
            Tabs::make('menu')
                ->tabs([
                    Tab::make('Streams')->schema([
                        Text::make(
                            'Modifying these permissions may give users access to sensitive information.',
                        ),
                    ]),
                    Tab::make('Documents')->schema([
                        Livewire::make(FlowDocumentsTable::class, [
                            'flow' => $this->record,
                        ]),
                    ]),
                    Tab::make('Checkpoints')->schema([]),
                    Tab::make('Memebers')->schema([
                        Livewire::make(ManageMemebersTable::class, [
                            'roleableEntity' => $this->record,
                        ]),
                    ]),
                ])
                ->contained(),
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
        return $this->record->description
            ? ucfirst($this->record->description)
            : '';
    }

    public function getTitle(): string|Htmlable
    {
        return ucfirst($this->record->title);
    }
}
