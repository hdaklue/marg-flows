<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Actions\Flow\CreateFlow as CreateFlowAction;
use App\DTOs\Flow\CreateFlowDto;
use App\Exceptions\Flow\FlowCreationException;
use App\Filament\Pages\FlowsKanabanBoard;
use App\Filament\Resources\FlowResource;
use App\Forms\Components\ChunkedFileUpload;
use App\Forms\Components\EditorJs;
use App\Models\ModelHasRole;
use App\Models\User;
use App\Services\Flow\TemplateService;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class CreateFlow extends CreateRecord
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

                    Section::make([
                        TextInput::make('title')
                            ->required(),
                        Textarea::make('description')
                            ->required(),
                        ChunkedFileUpload::make('attachments')
                            ->maxFiles(5),

                        // EditorJs::make('blocks')
                        //     ->required()
                        //     ->columnSpanFull(),
                        // ChunkedFileUpload::make('as')
                        //     ->video()
                        //     ->maxFiles(5),
                    ])->columnSpan(3),
                    Section::make([
                        Select::make('template')
                            ->options(TemplateService::toArray())
                            ->selectablePlaceholder(false),
                        DatePicker::make('start_date')
                            ->minDate(today(filamentUser()->timezone))
                            ->required()
                            ->live(onBlur: true)
                            ->native(false),

                        DatePicker::make('due_date')
                            ->required()
                            ->minDate(fn ($get) => $get('start_date'))

                            ->afterOrEqual(fn ($get) => $get('start_date'))
                            ->native(false),
                        Select::make('participants')
                            // ->options(User::assignedTo(filamentTenant())->where('id', '!=', filamentUser()->getKey())->pluck('name', 'id'))
                            ->options($this->getParticipantsSelectArray())
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
            $this->redirect(FlowsKanabanBoard::getUrl(['tenant' => filamentTenant()]));

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

    protected function getCreatedNotification(): ?Notification
    {
        return null; // Disables automatic notification
    }

    private function getParticipantsSelectArray(): array
    {
        $participants = filamentTenant()->getParticipants()->reject(fn ($modelHasRole) => $modelHasRole->model->getKey() === filamentUser()->getKey());

        return $participants->mapWithKeys(fn (ModelHasRole $item) => [$item->model->getKey() => "{$item->model->getAttribute('name')} - {$item->role->name}"])->toArray();

    }
}
