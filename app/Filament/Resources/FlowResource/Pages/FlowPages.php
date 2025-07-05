<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\Flow;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Locked;

class FlowPages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FlowResource::class;

    protected static string $view = 'filament.resources.flow-resource.pages.flow-pages';

    public ?array $data = [];

    #[Locked]
    public Flow $flow;

    public function mount(string $record)
    {

        $this->flow = Flow::where('id', $record)->first();
        $this->form->fill($this->flow->toArray());
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Section::make([
                PlaceholderInput::make('title')
                    ->autofocus(true)
                    ->live(true)
                    ->afterStateUpdated(fn ($state) => $this->flow->update(['title' => $state]))
                    ->autocomplete(false)
                    ->placeholder('Title')
                    ->required(),
                EditorJs::make('blocks')
                    ->required(),
            ]),
        ])->statePath('data');
    }
}
