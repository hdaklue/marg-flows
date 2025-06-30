<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Actions\Flow\CreateFlow as CreateFlowAction;
use App\Filament\Resources\FlowResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;

class CreateFlow extends CreateRecord
{
    protected static string $resource = FlowResource::class;

    protected static string $view = 'filament.resources.flow-resource.pages.create-flow';

    protected static bool $canCreateAnother = false;

    protected static ?string $title = '';

    public string $from;

    public ?string $maxContentWidth = 'screen-2xl';

    protected ?bool $hasUnsavedDataChangesAlert = true;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make([
                    'lg' => 4,
                ])->schema([
                    PlaceholderInput::make('title')
                        ->autofocus(true)
                        ->autocomplete(false)
                        ->columnSpanFull()
                        ->placeholder('Title')
                        ->required(),
                    Section::make([

                        EditorJs::make('wiki')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(3),

                    Section::make([
                        DatePicker::make('start_date')
                            ->default(today())
                            ->live(onBlur: true)
                            ->native(false),
                        DatePicker::make('due_date')
                            ->required()
                            ->minDate(fn (Get $get) => $get('start_date'))
                            ->afterOrEqual('start_date')
                            ->native(false),
                        Select::make('participants')
                            ->options(User::memberOf(filament()->getTenant())
                                ->where('id', '!=', filament()->auth()->user()->id)
                                ->pluck('name', 'id'))
                            ->multiple(true)
                            ->native(false),
                        // FileUpload::make('assets')
                        //     ->multiple(),

                    ])->columnSpan(1),
                ]),

            ]);
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();
        $data = $this->form->getState();
        try {
            CreateFlowAction::run($data, filament()->getTenant(), filament()->auth()->user());
            $this->form->fill();
            Notification::make()
                ->body('Created Successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->body('Something went wrong')
                ->danger()
                ->send();
            Log::error($e->getMessage());
        }

    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }
}
