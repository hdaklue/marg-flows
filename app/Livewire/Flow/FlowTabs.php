<?php

declare(strict_types=1);

namespace App\Livewire\Flow;

use App\Livewire\Participants\ManageParticipantsTable;
use App\Models\Flow;
use Hdaklue\NapTab\Livewire\NapTab;
use Hdaklue\NapTab\UI\Tab;
use Livewire\Attributes\Locked;

final class FlowTabs extends NapTab
{
    #[Locked]
    public string $flowId;

    public function tabs(): array
    {
        $flow = Flow::where('id', $this->flowId)->first();

        return [
            Tab::make('streams')
                ->icon('arrows-right-left')
                ->label(__('flow.tabs.streams')),
            Tab::make('documents')
                ->icon('clipboard-document-list')
                ->label(__('flow.tabs.documents'))
                ->livewire(FlowDocumentsTable::class, [
                    'flow' => $flow,
                ]),
            Tab::make('members')
                ->icon('user-group')
                ->label(__('flow.tabs.members'))
                ->visible(fn () => filamentUser()->can('manage', $flow))
                ->livewire(ManageParticipantsTable::class, [
                    'roleableEntity' => $flow,
                ]),
        ];
    }
}
