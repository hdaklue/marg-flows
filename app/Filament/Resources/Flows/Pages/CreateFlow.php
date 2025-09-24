<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Actions\Flow\CreateFlow as CreateFlowAction;
use App\DTOs\Flow\CreateFlowDto;
use App\Enums\FlowStage;
use App\Exceptions\Flow\FlowCreationException;
use App\Filament\Pages\FlowsKanabanBoard;
use App\Filament\Resources\Flows\FlowResource;
use App\Forms\Components\ChunkedFileUpload;
use App\Forms\Components\EditorJs;
use App\Models\ModelHasRole;
use App\Models\User;
use App\Services\Deliverable\DeliverableSpecResolver;
use App\Services\Flow\TemplateService;
use App\ValueObjects\Deliverable\DeliverableFormat;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Width;
use Hdaklue\Porter\RoleFactory;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class CreateFlow extends CreateRecord
{
    use HasWizard;

    protected static string $resource = FlowResource::class;

    protected static bool $canCreateAnother = false;

    protected static null|string $title = '';

    public $start = 3;

    public string $from;

    protected string $view = 'filament.resources.flow-resource.pages.create-flow';

    // public ?string $maxContentWidth = 'screen-7xl';

    protected null|bool $hasUnsavedDataChangesAlert = true;

    // public function form(Form $form): Form
    // {

    //     return $form
    //         ->schema([
    //             Wizard::make([
    //                 Step::make('Flow Details')
    //                     ->description('Provide the details of the flow you want to create.')
    //                     ->schema([
    //                         TextInput::make('title')
    //                             ->required()
    //                             ->label('Flow Title'),
    //                         Textarea::make('description')
    //                             ->required()
    //                             ->label('Description'),
    //                         // ChunkedFileUpload::make('attachments')
    //                         //     ->maxFiles(5)
    //                         //     ->label('Attachments'),
    //                     ]),
    //                 Step::make('Template')
    //                     ->description('Select a template for the flow.')
    //                     ->schema([
    //                     ]),
    //             ]),
    //             // Section::make([
    //             //     TextInput::make('title')
    //             //         ->required(),
    //             //     Textarea::make('description')
    //             //         ->required(),
    //             //     // ChunkedFileUpload::make('attachments')
    //             //     //     ->maxFiles(5),

    //             //     // EditorJs::make('blocks')
    //             //     //     ->required()
    //             //     //     ->columnSpanFull(),
    //             //     // ChunkedFileUpload::make('as')
    //             //     //     ->video()
    //             //     //     ->maxFiles(5),
    //             // ])->columnSpan(3),
    //             // Section::make([
    //             //     Select::make('template')
    //             //         ->options(TemplateService::toArray())
    //             //         ->selectablePlaceholder(false),
    //             //     DatePicker::make('start_date')
    //             //         ->minDate(today(filamentUser()->timezone))
    //             //         ->required()
    //             //         ->live(onBlur: true)
    //             //         ->native(false),

    //             //     DatePicker::make('due_date')
    //             //         ->required()
    //             //         ->minDate(fn ($get) => $get('start_date'))

    //             //         ->afterOrEqual(fn ($get) => $get('start_date'))
    //             //         ->native(false),
    //             //     Select::make('participants')
    //             //         // ->options(User::assignedTo(filamentTenant())->where('id', '!=', filamentUser()->getKey())->pluck('name', 'id'))
    //             //         ->options($this->getParticipantsSelectArray())
    //             //         ->multiple(true)
    //             //         ->native(false),
    //             //     // FileUpload::make('assets')
    //             //     //     ->multiple(),

    //             // ])->columnSpan(1),

    //         ]);
    // }

    public function getMaxContentWidth(): Width
    {
        return Width::SevenExtraLarge;
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();
        $data = $this->form->getState();

        try {
            $flowDto = CreateFlowDto::fromArray($data);
        } catch (ValidationException $e) { // @phpstan-ignore-line
            Log::error($e->getMessage());
        }

        try {
            CreateFlowAction::run($flowDto, filamentTenant(), filamentUser());

            $this->form->fill();

            Notification::make()
                ->body('Created Successfully')
                ->success()
                ->send();

            // $this->redirect(FlowsKanabanBoard::getUrl(['tenant' => filamentTenant()]));
        } catch (FlowCreationException $e) {
            Notification::make()
                ->body('We could not create the flow')
                ->danger()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->body('Something went wrong')
                ->danger()
                ->send();
            Log::error($e->getMessage());
        }
    }

    public function getHeading(): string|Htmlable // @phpstan-ignore-line
    {
        return '';
    }

    // protected function getSteps(): array
    // {
    //     return [
    //         Step::make('Flow Details')
    //             ->description('Provide the details of the flow you want to create.')
    //             ->schema([
    //                 ChunkedFileUpload::make('files'),
    //                 TextInput::make('title')
    //                     ->required()
    //                     ->label('Flow Title'),

    //                 Textarea::make('description')
    //                     ->required()
    //                     ->label('Description'),
    //                 Select::make('stage')
    //                     ->options(FlowStage::class)
    //                     ->default(FlowStage::DRAFT),
    //                 // ChunkedFileUpload::make('attachments')
    //                 //     ->maxFiles(5)
    //                 //     ->label('Attachments'),
    //             ]),
    //         Step::make('deliverables')
    //             ->label('Deliverables')
    //             ->description(fn ($get): string => 'Add deliverables for the flow. You can add multiple deliverables.')
    //             ->schema([
    //                 Repeater::make('delivrables')
    //                     ->table([
    //                         TableColumn::make('title'),
    //                         TableColumn::make('format'),
    //                         TableColumn::make('type'),
    //                         TableColumn::make('# of Options'),
    //                         TableColumn::make('success_date'),
    //                     ])
    //                     ->schema(
    //                         [

    //                             TextInput::make('Title'),
    //                             Select::make('template')
    //                                 ->options(DeliverableSpecResolver::getSupportedFormats())
    //                                 ->live()
    //                                 ->native(false)
    //                                 ->partiallyRenderComponentsAfterStateUpdated(['urgency'])
    //                                 ->selectablePlaceholder(false)
    //                                 ->required(),
    //                             Select::make('urgency')
    //                                 ->multiple(false)
    //                                 ->disabled(fn (Get $get) => empty($get('template')))
    //                                 ->options(fn ($get) => $get('template') ? (new DeliverableFormat($get('template')))->typesAsSelectArray() : [])
    //                                 ->native(false),
    //                             TextInput::make('options')
    //                                 ->numeric()
    //                                 ->default(1)
    //                                 ->minValue(1)
    //                                 ->maxValue(5),
    //                             DatePicker::make('sucess_date')
    //                                 ->closeOnDateSelection()
    //                                 ->native(false)
    //                                 ->minDate(today(filamentUser()->timezone)),
    //                         ],
    //                     )
    //                     ->cloneable()
    //                     ->grid(1)
    //                     ->reorderable(false)
    //                     ->collapsible(),
    //             ]),
    //         Step::make('participatns')
    //             ->label('Participants')
    //             ->description(fn ($get): string => 'Select participants for the flow. You can select multiple participants.')
    //             ->schema([
    //                 Toggle::make('custom_members'),
    //                 Repeater::make('participants')
    //                     ->schema([
    //                         Select::make('participant_id')
    //                             ->options($this->getParticipantsSelectArray())
    //                             ->selectablePlaceholder(false)
    //                             ->required(),
    //                         Select::make('role_id')
    //                             ->options(RoleFactory::getAllWithKeys())
    //                             ->selectablePlaceholder(false)
    //                             ->required(),
    //                     ])
    //                     ->visibleJs(<<<'JS'
    //                              $get('custom_members')
    //                             JS)
    //                     ->grid(2)
    //                     ->reorderable(false)
    //                     ->collapsible(),
    //             ]),
    //     ];
    // }

    protected function getCreatedNotification(): null|Notification
    {
        return null; // Disables automatic notification
    }

    private function getParticipantsSelectArray(): array
    {
        $participants = filamentTenant()->getParticipants()->pluck('assignable');

        // return $participants->mapWithKeys(fn (ModelHasRole $item) => [$item->model->getKey() => "{$item->model->getAttribute('name')} - {$item->role->name}"])->toArray();

        return $participants->toArray();
    }
}
