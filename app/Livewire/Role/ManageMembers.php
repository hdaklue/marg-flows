<?php

declare(strict_types=1);

namespace App\Livewire\Role;

use App\Actions\Roleable\AddParticipant;
use App\Actions\Roleable\RemoveParticipant;
use App\Contracts\Roles\HasParticipants;
use App\Enums\Role\RoleEnum;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;

#[Lazy(\true)]
class ManageMembers extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    public ?Collection $manageableMembers;

    public ?HasParticipants $roleable = null;

    public ?array $data = [];

    public function mount(?HasParticipants $roleable)
    {
        if ($roleable) {
            $this->authorize('manageMembers', $roleable);
            $this->form->fill();
            $this->roleable = $roleable;
        }
        $this->manageableMembers = $this->loadManageableMembers();

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

                        return User::memberOf(\filament()->getTenant())
                            ->whereNotIn('id', $this->roleable->participants->pluck('id')->toArray())
                            ->get()->pluck('name', 'id');
                    })
                    ->required(),
                Select::make('role')
                    ->required()
                    ->options(function () {
                        if (! $this->roleable) {
                            return [];
                        }
                        $userRole = Auth::user()->rolesOn($this->roleable)->firstOrFail();

                        return RoleEnum::whereLowerThanOrEqual(RoleEnum::from($userRole->name))->toArray();
                    }),
                // ...
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function loadManageableMembers(): Collection
    {
        if (! $this->roleable) {
            return collect();
        }

        return $this->roleable->participants->filter(fn ($item) => $item->id != filament()->auth()->user()->id);
    }

    public function addMember()
    {
        $this->authorize('manageMembers', $this->roleable);
        $state = $this->form->getState();

        $user = User::where('id', '=', $state['member'])->firstOrFail();
        $role = RoleEnum::from($state['role']);
        AddParticipant::run($this->roleable, $user, $role);

        $this->manageableMembers = $this->loadmanageableMembers();
        $this->form->fill();
        $this->dispatch("board-item-updated.{$this->roleable->id}");

    }

    public function removeMemberAction(): Action
    {
        return Action::make('Remove Member')
            ->action(function (array $arguments) {
                $user = User::where('id', '=', $arguments['memberId'])->firstOrFail();
                RemoveParticipant::run($this->roleable, $user);
                $this->manageableMembers = $this->loadmanageableMembers();

                $this->dispatch("board-item-updated.{$this->roleable->id}");
            })->requiresConfirmation()
            ->color('danger');

    }

    // #[On('members-updated')]
    public function render()
    {
        return view('livewire.role.manage-members');
    }
}
