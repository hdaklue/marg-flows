<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Services\Recency\RecentableCollection;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class RecentableList extends Component implements HasActions, HasSchemas
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
