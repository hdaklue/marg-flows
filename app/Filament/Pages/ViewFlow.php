<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Flow\EditFlow;
use App\Filament\Resources\FlowResource;
use App\Models\Flow;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

/**
 * ViewFlow.
 *
 * @property-read Flow $flow;
 * @property-read array $getParticipantsArray
 */
final class ViewFlow extends KanbanBoard
{
    protected static string $model = Flow::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'task-kanban.kanban-board';

    protected static string $headerView = 'task-kanban.kanban-header';

    protected static string $statusView = 'task-kanban.kanban-status';

    protected static string $scriptsView = 'task-kanban.kanban-scripts';

    #[Url('record')]
    #[Locked]
    public string $recordId;

    protected ?string $maxContentWidth = 'full';

    #[Computed]
    public function flow(): Flow
    {
        return Flow::where('id', $this->recordId)->firstOrFail();
    }

    #[Computed]
    public function canManageFlow(): bool
    {
        return filamentUser()->can('manageFlows', filamentTenant());
    }

    public function getHeading(): string|Htmlable // @phpstan-ignore-line
    {
        return $this->flow->title;
    }

    public function getSubheading(): string|Htmlable|null // @phpstan-ignore-line
    {
        return $this->flow->description;
    }

    #[On('board-item-updated.{recordId}')]
    public function reloadMembers()
    {
        unset($this->getParticipantsArray);
    }

    #[Computed]
    public function getParticipantsArray(): array
    {
        return $this->flow->getParticipants()->pluck('model')->map(fn ($item) => ['name' => $item->name, 'avatar' => $item->avatar])->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->authorize('update', $this->flow)
                ->outlined()
                ->color('gray')
                ->icon('heroicon-o-pencil-square')
                ->size(ActionSize::ExtraSmall)
                ->fillForm(fn () => [
                    'title' => $this->flow->title,

                    'due_date' => $this->flow->getProgressDueDate(),
                    'description' => $this->flow->description,
                ])
                ->form([
                    Grid::make([
                        'sm' => 1,
                        'lg' => 1,
                    ])->schema([
                        TextInput::make('title')
                            ->required()
                            ->minLength(5)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->required(),
                        // DatePicker::make('start_date')
                        //     ->native(false),
                        DatePicker::make('due_date')
                            ->minDate($this->flow->getProgressStartDate())
                            ->required()
                            ->native(false),
                    ]),
                ])
                ->action(function (array $data) {
                    try {
                        EditFlow::run($this->flow, $data['title'],
                            $data['description'], Carbon::parse($data['due_date']));
                        Notification::make()
                            ->body('Updated successfully')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->body('Something went wrong')
                            ->danger()
                            ->send();
                        logger()->error($e->getMessage());
                    }
                })
                ->modalWidth(MaxWidth::Large),
            Action::make('add')
                ->label('Task')
                ->color('gray')
                ->size(ActionSize::ExtraSmall)
                ->icon('heroicon-o-plus-circle')
                ->outlined()
                ->url(FlowResource::getUrl('pages', ['record' => $this->recordId])),
            Action::make('view')
                ->label('Pages')
                ->color('gray')
                ->size(ActionSize::ExtraSmall)
                ->icon('heroicon-o-document-text')
                ->outlined()
                ->url(FlowResource::getUrl('pages', ['record' => $this->recordId])),
        ];
    }

    protected function records(): Collection
    {

        $this->authorize('view', $this->flow);

        return collect([]);
    }

    protected function statuses(): Collection
    {

        return $this->flow->stages->map(fn ($item) => $item->toArray());
    }
}
