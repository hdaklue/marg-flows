<?php

declare(strict_types=1);

namespace App\Livewire\Tenancy;

use Livewire\Attributes\On;
use Livewire\Component;

final class ManageMembersTab extends Component
{
    #[On('InviteMemberAction::InvitationsSent')]
    public function handleInvitationSent(): void
    {
        $this->dispatch('PendingInvitationTable::refresh');
    }

    public function render()
    {
        return view('livewire.tenancy.manage-members-tab');
    }
}
