<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\Page as PageModel;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/**
 * @property-read bool $canEdit
 * @property-read Form $form
 */
final class ViewPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.view-page';

    protected static ?string $title = '';

    public ?array $data = [];

    #[Url('record')]
    #[Locked]
    public string $recordKey;

    public $record = null;

    public function mount(): void
    {

        $this->record = PageModel::where('id', $this->recordKey)->firstOrFail();

        $this->esnsureTenantIntegrity();

        // abort_if(filamentTenant()->getKey() !== $this->record->getTenant(), 404, '');
        $this->authorize('view', $this->record);

        $this->form->fill([
            'title' => $this->record->getAttribute('name'),
            'blocks' => $this->record->getAttribute('blocks'),
        ]);

    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                PlaceholderInput::make('title')
                    ->editable($this->canEdit)
                    ->required()
                    ->live(debounce: '100ms')
                    ->minLength(10)
                    ->afterStateUpdated(function ($state, $livewire) {
                        $livewire->validate();

                        if (! $this->canEdit() || blank($state)) {
                            return;
                        }

                        $this->record->update([
                            'name' => $state,
                        ]);
                    }),
                // EditorJs::make('blocks')
                //     ->editable($this->canEdit)
                //     ->live()
                //     ->afterStateUpdated(fn ($state) => $this->record->update([
                //         'blocks' => $state,
                //     ])),
            ])
            ->statePath('data');
    }

    #[Computed()]
    public function canEdit(): bool
    {
        return filamentUser()->can('update', $this->record);
    }

    private function esnsureTenantIntegrity(): void
    {
        abort_if($this->record->getTenant()->getKey() !== filamentTenant()->getKey(), 404);
    }
}
