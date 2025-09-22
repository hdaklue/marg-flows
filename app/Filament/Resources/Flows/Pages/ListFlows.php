<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Enums\FlowStage;
use App\Filament\Resources\Flows\Actions\CreateFlowAction;
use App\Filament\Resources\Flows\FlowResource;
use App\Filament\Resources\Flows\Schemas\FlowsTable;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ListFlows extends ListRecords
{
    use HasResizableColumn;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->running()),
            'blocked' => Tab::make()
                ->label(__('flow.tabs.blocked'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where(
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
            CreateFlowAction::make($this),
        ];
    }
}
