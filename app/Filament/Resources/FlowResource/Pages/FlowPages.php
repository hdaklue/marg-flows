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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;

/**
 * @property-read \Filament\Forms\Form $form
 */
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
        $this->authorize('view', $this->flow);
        $this->form->fill($this->flow->toArray());
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function form(Form $form): Form
    {

        return $form->schema([

            Section::make([
                PlaceholderInput::make('title')
                    ->autofocus(true)
                    ->live(true)
                    ->readOnly(fn () => ! filamentUser()->can('update', $this->flow))
                    ->afterStateUpdated(function ($set, $state, $old) {
                        if (blank($state)) {
                            Notification::make()
                                ->body('Title cant be empty')
                                ->danger()
                                ->color('danger')
                                ->send();
                            $set('title', $old);

                            return;
                        }
                        $this->flow->update(['title' => $state]);
                    })
                    ->autocomplete(false)
                    ->placeholder('Title')
                    ->required(),

                EditorJs::make('blocks')
                    ->editable(fn () => filamentUser()->can('update', $this->flow))
                    ->live(true)
                    ->afterStateUpdated(fn ($state) => $this->flow->update(['blocks' => $state]))
                    ->required(),
            ]),
        ])->statePath('data');
    }
}
