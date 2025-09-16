<?php

declare(strict_types=1);

namespace App\Livewire\Participants;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ManageParticipantsModal extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public RoleableEntity $roleableEntity;

    public function mount(RoleableEntity $roleableEntity)
    {
        $this->roleableEntity = $roleableEntity;
    }

    public function render(): View
    {
        return view('livewire.participants.manage-participants-modal');
    }
}
