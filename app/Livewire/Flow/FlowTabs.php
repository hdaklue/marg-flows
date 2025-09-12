<?php

declare(strict_types=1);

namespace App\Livewire\Flow;

use App\Filament\Schemas\ParticipantsInfolist;
use App\Livewire\Participants\ManageParticipantsTable;
use App\Models\Flow;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\UI\Tab;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Locked;

final class FlowTabs extends NapTab
{
    #[Locked]
    public string $flowId;

    public function tabs(): array
    {
        $flow = Flow::where('id', $this->flowId)->first();

        return [
            Tab::make('streams')->icon('arrows-right-left')->label(__('flow.tabs.streams')),
            Tab::make('documents')
                ->badge('40')
                ->visible(fn() => filamentUser()->can('manage', $flow))
                ->icon('clipboard-document-list')
                ->label(__('flow.tabs.documents'))
                ->livewire(FlowDocumentsTable::class, [
                    'flow' => $flow,
                ]),
            Tab::make('members')
                ->icon('user-group')
                ->label(__('flow.tabs.members'))
                ->livewire(ManageParticipantsTable::class, [
                    'roleableEntity' => $flow,
                ]),
        ];
    }

    public function streams()
    {
        return new HtmlString('hello');
    }
}
