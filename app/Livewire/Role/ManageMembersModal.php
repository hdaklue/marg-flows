<?php

declare(strict_types=1);

namespace App\Livewire\Role;

use App\Contracts\Role\RoleableEntity;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Component;

final class ManageMembersModal extends Component
{
    public ?RoleableEntity $record = null;

    public $shouldShowModal = false;

    #[On('open-members-modal')]
    public function handleOpenMemebersModal(string $roleableKey, string $roleableType)
    {
        $modelClass = Relation::getMorphedModel($roleableType);
        $this->record = call_user_func([$modelClass, 'query'])->where('id', $roleableKey)->firstOrFail();

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
