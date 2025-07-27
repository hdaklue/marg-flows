<?php

declare(strict_types=1);

namespace App\Livewire\Role;

use App\Actions\Roleable\AddParticipant;
use App\Actions\Roleable\RemoveParticipant;
use App\Contracts\Role\RoleableEntity;
use App\Enums\Role\RoleEnum;
use App\Facades\RoleManager;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

use const true;

/**
 * @property-read Form $form
 * @property-read Collection $manageableMembers
 * @property-read Collection $authedUserAssignableRoles
 */
#[Lazy(true)]
final class ManageMembers extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    // public ?Collection $manageableMembers;

    public ?RoleableEntity $roleable = null;

    public ?array $data = [];

    #[Locked]
    public bool $canEdit = false;

    public function mount(?RoleableEntity $roleable)
    {
        if ($roleable) {
            $this->canEdit = $this->authorize('manageMembers', $roleable)->allowed();
            $this->form->fill();
            $this->roleable = $roleable;
        }
        // $this->manageableMembers = $this->loadManageableMembers();

    }

    public function form(Form $form): Form
    {

        return $form
            ->schema([
                Select::make('member')
                    ->options(function () {
                        if (! $this->roleable) {
                            return [];
                        }

                        return User::assignedTo(filamentTenant())->notAssignedTo($this->roleable)
                            ->get()->pluck('name', 'id');
                    })
                    ->required(),
                Select::make('role')
                    ->required()
                    ->options(function () {
                        if (! $this->roleable) {
                            return [];
                        }
                        $userRole = filamentUser()->getAssignmentOn($this->roleable);

                        return RoleEnum::whereLowerThanOrEqual(RoleEnum::from($userRole->name))->toArray();
                    }),
                // ...
            ])
            ->columns(2)
            ->statePath('data');
    }

    #[Computed]
    public function manageableMembers(): Collection
    {

        if (! $this->roleable) {
            return collect();
        }

        return $this->roleable->getParticipants()->filter(fn ($item) => $item->model->getKey() !== filamentUser()->getKey());
    }

    public function addMember()
    {
        $this->authorize('manageMembers', $this->roleable);
        $state = $this->form->getState();

        $user = User::where('id', '=', $state['member'])->firstOrFail();
        $role = RoleEnum::from($state['role']);
        AddParticipant::run($this->roleable, $user, $role);
        $this->reloadData();

    }

    #[Computed]
    public function authedUserAssignableRoles(): Collection
    {
        if (! $this->roleable) {
            return collect();
        }
        $role = filamentUser()->getAssignmentOn($this->roleable);

        return RoleEnum::getRolesLowerThanOrEqual(RoleEnum::from($role->name));
    }

    public function changeRole(string $role, string|int $target_id)
    {
        $this->authorize('manageMembers', $this->roleable);
        $targetModel = $this->roleable->getParticipant($target_id);
        if (! $targetModel) {
            return;
        }

        RoleManager::changeRoleOn($targetModel, $this->roleable, $role);
        $this->reloadData();
        Notification::make()
            ->body('Role updated successfully')
            ->success()
            ->send();
    }

    public function removeMemberAction(): Action
    {

        return Action::make('Remove Member')
            ->action(function (array $arguments) {
                $user = User::where('id', '=', $arguments['memberId'])->firstOrFail();
                RemoveParticipant::run($this->roleable, $user);
                $this->reloadData();
            })->requiresConfirmation()
            ->iconButton()
            ->color('danger');

    }

    // #[On('members-updated')]
    public function render()
    {
        return view('livewire.role.manage-members');
    }

    private function reloadData()
    {
        unset($this->manageableMembers);
        $this->form->fill();
        $this->dispatch("board-item-updated.{$this->roleable->getKey()}");
        $this->dispatch("roleable-entity:members-updated.{$this->roleable->getKey()}");
    }
}
