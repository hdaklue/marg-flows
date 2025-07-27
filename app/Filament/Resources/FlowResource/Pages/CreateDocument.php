<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Enums\Role\RoleEnum;
use App\Filament\Resources\FlowResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\Flow;
use App\Models\ModelHasRole;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

/**
 * @property-read Form $form
 */
final class CreateDocument extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FlowResource::class;

    protected static string $view = 'filament.resources.flow-resource.pages.create-document';

    public ?array $data = [];

    public Flow $flow;

    public function mount(string $flow)
    {
        // $this->flow = Flow::where('id', $flow)->firstOrFail();
        $this->authorize('manageFlow', $this->flow);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('Available only for')
                ->hint('Assign to specific Users or leave it for all users')
                ->native(false)
                ->multiple()
                ->searchable()
                ->options(fn () => $this->flow->getParticipants()->filter(fn (ModelHasRole $item) => $item->model->getKey() !== filamentUser()->getKey())
                    ->mapWithKeys(fn (ModelHasRole $item) => [$item->model->getKey() => $item->model->getAttribute('name') . ' - ' . RoleEnum::from($item->role->getAttribute('name'))->getLabel()]))
                ->columnSpan(1),
            Section::make([
                PlaceholderInput::make('name')
                    ->required()
                    ->minLength(10)
                    ->columnSpanFull()
                    ->Placeholder('name'),
                EditorJs::make('blocks')->columnSpanFull(),
            ])->columns(3),
            Actions::make([
                FormAction::make('save')
                    ->color('primary')
                    ->action(fn ($data) => dd($this->form->getState())),
            ]),
        ])->statePath('data');
    }
}
