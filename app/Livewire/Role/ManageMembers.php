<?php

declare(strict_types=1);

namespace App\Livewire\Role;

use App\Actions\Roleable\AddParticipant;
use App\Actions\Roleable\RemoveParticipant;
use App\DTOs\Roles\ParticipantsDto;
use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

use const true;

/**
 * @property-read Schema $form
 * @property-read ParticipantsCollection $manageableMembers
 * @property-read Collection $authedUserAssignableRoles
 * @property-read array $assignableEntities
 */
#[Lazy(true)]
final class ManageMembers extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    // public ?Collection $manageableMembers;

    public null|RoleableEntity $roleableEntity = null;

    public null|RoleableEntity $scopeTo = null;

    public null|array $data = [];

    #[Locked]
    public bool $canEdit = false;

    public function mount(null|RoleableEntity $roleableEntity, null|RoleableEntity $scopeToEntity)
    {
        if ($roleableEntity) {
            $this->canEdit = $this->authorize('manageMembers', $roleableEntity)->allowed();

            $this->roleableEntity = $roleableEntity;
        }

        if ($scopeToEntity) {
            $this->scopeTo = $scopeToEntity;
        }
        $this->form->fill();

        // $this->manageableMembers = $this->loadManageableMembers();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member')
                    ->label(__('app.users'))
                    ->options(fn() => $this->assignableEntities)
                    ->required(),
                Select::make('role')
                    ->label(__('app.role'))
                    ->required()
                    ->native(false)
                    ->options(function () {
                        if (!$this->roleableEntity) {
                            return [];
                        }
                        $userRole = filamentUser()->getAssignmentOn($this->roleableEntity);

                        return RoleEnum::whereLowerThanOrEqual(RoleEnum::from($userRole->name))->toArray();
                    }),
                // ...
            ])
            ->columns(2)
            ->statePath('data');
    }

    #[Computed]
    public function manageableMembers(): ParticipantsCollection
    {
        if (!$this->roleableEntity) {
            return new ParticipantsCollection();
        }

        return $this->roleableEntity
            ->getParticipants()
            ->exceptAssignable(filamentUser())
            ->asDtoArray();

        // return ParticipantsDto::fromParticipantsCollection(
        //     $this->roleable->getParticipants()->exceptAssignable(filamentUser()->getKey()),
        // );
    }

    public function addMember()
    {
        $this->authorize('manageMembers', $this->roleableEntity);
        $state = $this->form->getState();

        $user = User::where('id', '=', $state['member'])->firstOrFail();
        $role = RoleEnum::from($state['role']);
        AddParticipant::run($this->roleableEntity, $user, $role);

        $this->form->fill();
        $this->reloadData();

        Notification::make()
            ->body(__('app.created_successfully'))
            ->success()
            ->send();
    }

    #[Computed]
    public function authedUserAssignableRoles(): Collection
    {
        if (!$this->roleableEntity) {
            return collect();
        }
        $role = filamentUser()->getAssignmentOn($this->roleableEntity);

        return RoleEnum::getRolesLowerThanOrEqual(RoleEnum::from($role->name));
    }

    public function changeRole(string $role, string|int $target_id)
    {
        $this->authorize('manageMembers', $this->roleableEntity);
        $targetModel = $this->roleableEntity->getParticipant($target_id);
        if (!$targetModel) {
            return;
        }

        RoleManager::changeRoleOn($targetModel, $this->roleableEntity, $role);
        $this->reloadData();
        Notification::make()
            ->body(__('app.updated_successfully'))
            ->success()
            ->send();
    }

    public function removeMember(string $memberId)
    {
        $this->authorize('manageMembers', $this->roleableEntity);

        $user = User::where('id', '=', $memberId)->firstOrFail();
        RemoveParticipant::run($this->roleableEntity, $user);
        $this->reloadData();

        Notification::make()
            ->body(__('app.deleted_successfully'))
            ->success()
            ->send();
    }

    #[Computed]
    public function assignableEntities(): array
    {
        return $this->resolveAssignableEntities();
    }

    // #[On('members-updated')]
    public function render()
    {
        return view('livewire.role.manage-members');
    }

    protected function resolveAssignableEntities(): array
    {
        if (!$this->roleableEntity) {
            return [];
        }

        if ($this->scopeTo && $this->roleableEntity) {
            $assignedIds = $this->roleableEntity
                ->getParticipants()
                ->getParticipantIds()
                ->toArray();

            return $this->scopeTo
                ->getParticipants()
                ->exceptAssignable(Arr::prepend($assignedIds, filamentUser()->getKey()))
                ->getParticipantsAsSelectArray();
        }

        return filamentTenant()
            ->getParticipants()
            ->exceptAssignable(filamentUser())
            ->getParticipantsAsSelectArray();
    }

    private function reloadData()
    {
        unset($this->manageableMembers, $this->assignableEntities);

        $this->dispatch("board-item-updated.{$this->roleableEntity->getKey()}");
        $this->dispatch("roleable-entity:members-updated.{$this->roleableEntity->getKey()}");
    }
}
