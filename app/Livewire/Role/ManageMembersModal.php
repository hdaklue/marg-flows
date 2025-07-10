<?php

declare(strict_types=1);

namespace App\Livewire\Role;

use App\Contracts\Role\RoleableEntity;
use App\Models\Flow;
use Livewire\Attributes\On;
use Livewire\Component;

final class ManageMembersModal extends Component
{
    public ?RoleableEntity $record = null;

    public $shouldShowModal = false;

    #[On('open-members-modal')]
    public function handleOpenMemebersModal(string|int $roleable)
    {
        $this->record = Flow::where('id', $roleable)->firstOrFail();

        $this->dispatch('open-modal', id: 'edit-members-modal');
    }

    #[On('close-modal')]
    public function handleCloseModal($id = null)
    {
        if ($id === 'edit-members-modal') {
            $this->record = null;
        }
    }

    public function render()
    {
        return view('livewire.role.manage-members-modal');
    }
}
