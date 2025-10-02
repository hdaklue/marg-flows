<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Concerns\Filament\Pages\DisableBreadcrumb;
use App\Filament\Actions\Document\CreateDocumentAction;
use App\Filament\Actions\Flow\EditFlowInfoAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Livewire\Flow\FlowTabs;
use App\Services\Recency\Actions\RecordRecency;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Hdaklue\Actioncrumb\Traits\HasActionCrumbs;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Throwable;

/**
 * @property-read Collection $stages
 */
final class ViewFlow extends ViewRecord
{
    use DisableBreadcrumb, HasActionCrumbs;

    protected static string $resource = FlowResource::class;

    protected string $view = 'filament.resources.flow-resource.pages.view';

    protected array $sortableRules = [
        'items' => ['required', 'array', 'max:50'],
        'items.*' => ['required', 'string'],
    ];

    public function getHeading(): Htmlable|string
    {
        return '';
    }

    public function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('add_stream')
                    ->label(__('flow.actions.stream'))
                    ->size(Size::ExtraSmall),
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
                // EditAction::make('edit')
                //     ->visible(filamentUser()->can('update', $this->record))
                //     ->record($this->record)
                //     ->fillForm([
                //         'title' => $this->record->getAttribute('title'),
                //         'description' => $this->record->getAttribute('description'),
                //     ])
                //     ->schema([
                //         TextInput::make('title'),
                //         Textarea::make('description'),
                //     ]),
                EditFlowInfoAction::make($this->record),
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

    /**
     * @throws Throwable
     */
    public function content(Schema $schema): Schema
    {
        RecordRecency::dispatch(filamentUser(), $this->record);

        return $schema->components([
            // Text::make(fn() => $this->record->getAttribute('title'))->size(TextSize::Large),
            Livewire::make(FlowTabs::class, ['flowId' => $this->record->getKey()]),
        ]);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->description ? ucfirst($this->record->description) : '';
    }

    public function getTitle(): string|Htmlable
    {
        return ucfirst($this->record->title);
    }
}
