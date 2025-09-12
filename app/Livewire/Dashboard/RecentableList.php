<?php

namespace App\Livewire\Dashboard;

use App\Services\Recency\RecentableCollection;
use Filament\Actions\Concerns\HasInfolist;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecentableList extends Component implements HasSchemas, HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Locked]
    protected array $records;

    public function mount(RecentableCollection $recents)
    {
        $this->records = $recents->asFlatDataCollection()->toArray();
    }

    #[Computed]
    public function recents(): array
    {
        return $this->records;
    }

    public function render()
    {
        return view('livewire.dashboard.recentable-list');
    }
}
