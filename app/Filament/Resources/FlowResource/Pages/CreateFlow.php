<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Actions\Flow\CreateFlow as CreateFlowAction;
use App\DTOs\Flow\CreateFlowDto;
use App\Exceptions\Flow\FlowCreationException;
use App\Filament\Resources\FlowResource;
use App\Forms\Components\EditorJs;
use App\Forms\Components\PlaceholderInput;
use App\Models\User;
use App\Services\Flow\TemplateService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                        Select::make('template')
                            ->options(TemplateService::toArray())
                            ->selectablePlaceholder(false),
                        DatePicker::make('start_date')
                            ->timezone(auth()->user()->timezone)
                            ->minDate(today())
                            ->required()
                            ->live(onBlur: true)
                            ->native(false),

                        DatePicker::make('due_date')
                            ->required()
                            ->timezone(auth()->user()->timezone)
                            ->afterOrEqual(fn($get)=>$get('start_date'))
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
        // $data['start_date'] = fromUserDateTime($data['start_date'],auth()->user());
        // $data['due_date'] = fromUserDateTime($data['due_date'],auth()->user());

        try {
            $flowDto = CreateFlowDto::fromArray($data);
        } catch (ValidationException $e) {
            Log::error($e->getMessage());
        }

        try {
            CreateFlowAction::run($flowDto, filament()->getTenant(), filament()->auth()->user());
            $this->form->fill();
            Notification::make()
                ->body('Created Successfully')
                ->success()
                ->send();

        } catch (FlowCreationException $e) {
            Notification::make()
                ->body('We could not create the flow')
                ->danger()
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
