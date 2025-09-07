<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Actions\Flow\CreateFlow;
use App\DTOs\Flow\CreateFlowDto;
use App\Enums\FlowStage;
use App\Filament\Resources\Flows\FlowResource;
use App\Filament\Resources\Flows\Schemas\FlowsTable;
use App\Models\Flow;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ListFlows extends ListRecords
{
    protected static string $resource = FlowResource::class;

    public function table(Table $table): Table
    {
        return FlowsTable::configure($table);
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make()
                ->label(__('flow.tabs.active'))
                ->modifyQueryUsing(fn(Builder $query) => $query->running()),
            'blocked' => Tab::make()
                ->label(__('flow.tabs.blocked'))
                ->modifyQueryUsing(fn(Builder $query) => $query->where(
                    'stage',
                    FlowStage::BLOCKED->value,
                )),
            'all' => Tab::make()->label(__('flow.tabs.all')),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(), // This method returns a component to display the tabs above a table
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->visible(filamentUser()->can('create', [
                    Flow::class,
                    filamentTenant(),
                ]))
                ->label(__('flow.actions.create'))
                ->outlined()
                ->form([
                    TextInput::make('title')->required()->maxLength(100),
                    Textarea::make('description')->maxLength(255),
                ])
                ->action(function (array $data) {
                    try {
                        $dto = CreateFlowDto::fromArray([
                            'title' => $data['title'],
                            'description' => $data['description'],
                        ]);

                        CreateFlow::run($dto, filamentTenant(), filamentUser());
                        Notification::make()
                            ->body(__('common.messages.operation_completed'))
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        logger()->error($e->getMessage());
                        Notification::make()
                            ->body(__('common.messages.operation_failed'))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
