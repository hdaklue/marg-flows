<?php

namespace App\Livewire\Flow;

use App\Contracts\Document\Documentable;
use App\Filament\Tables\Documents\DocumentsTable;
use App\Models\Flow;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FlowDocumentsTable extends Component implements
    HasActions,
    HasSchemas,
    HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    #[Locked]
    public Documentable $flow;

    public function mount(Documentable $flow)
    {
        $this->flow = $flow;
    }

    public function table(Table $table): Table
    {
        return DocumentsTable::configure($table, $this->flow);
    }

    public function render(): View
    {
        return view('livewire.flow.flow-documents-table');
    }
}
