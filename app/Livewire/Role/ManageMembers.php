<?php

declare(strict_types=1);

namespace App\Livewire\Role;

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
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;

#[Lazy(\true)]
class ManageMembers extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    public Collection $managableMembers;

    public $roleable;

    public ?array $data = [];

    public function mount(HasParticipants $roleable)
    {
        $this->form->fill();
        $this->roleable = $roleable;
        $this->loadManagableMembers();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('members')
                    ->options(function () {

                        return User::memberOf(\filament()->getTenant())
                            ->whereNotIn('id', $this->roleable->participants->pluck('id')->toArray())
                            ->get()->pluck('name', 'id');

                    }),
                Select::make('role')
                    ->options(RoleEnum::class),
                // ...
            ])
            ->columns(2)
            ->statePath('data');
    }

    #[On('members-updated')]
    public function loadManagableMembers()
    {
        $this->managableMembers = $this->roleable->participants->filter(fn ($item) => $item->id != filament()->auth()->user()->id);
    }

    public function removeMemberAction(): Action
    {
        return Action::make('Remove Member')
            ->action(function (array $arguments) {
                $user = User::where('id', '=', $arguments['memberId'])->first();
                $this->roleable->removeParticipant($user);
                $this->dispatch('members-updated');
            })->requiresConfirmation()
            ->color('danger');

    }

    // #[On('members-updated')]
    public function render()
    {
        return view('livewire.role.manage-members');
    }
}
