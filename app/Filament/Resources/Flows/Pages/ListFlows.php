<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Enums\FlowStage;
use App\Filament\Resources\Flows\FlowResource;
use App\Filament\Resources\Flows\Schemas\FlowsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraDumps\LaraDumps\Livewire\Attributes\Ds;

#[Ds]
final class ListFlows extends ListRecords
{
    protected static string $resource = FlowResource::class;

    protected Width|string|null $maxContentWidth = 'full';

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
            'draft' => Tab::make()
                ->label(__('flow.tabs.draft'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stage', FlowStage::DRAFT->value)),
            'all' => Tab::make()
                ->label(__('flow.tabs.all')),
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
            CreateAction::make()
                ->size(Size::Small),
        ];
    }
}
