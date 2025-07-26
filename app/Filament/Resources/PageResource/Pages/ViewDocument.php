<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\Pages;

use App\Facades\PageManager;
use App\Filament\Resources\PageResource;
use App\Forms\Components\PlaceholderInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;

final class ViewDocument extends ViewRecord
{
    protected static string $resource = PageResource::class;

    protected static string $view = 'filament.resources.page-resource.pages.view';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill([
            'title' => $this->record->getAttribute('name'),
        ]);
    }

    public function resolveRecord(int|string $key): Model
    {
        return PageManager::getPage($key);
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
                    ->placeholder('Page Title')
                    ->columnSpanFull()
                    ->maxLength(length: 100)
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

    #[Computed]
    public function canEdit(): bool
    {
        return filamentUser()->can('update', $this->record);
    }
}
