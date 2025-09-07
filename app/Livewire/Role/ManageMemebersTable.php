<?php

declare(strict_types=1);

namespace App\Livewire\Role;

use App\Actions\Roleable\AddParticipant;
use App\Actions\Roleable\RemoveParticipant;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Facades\Porter;
use Hdaklue\Porter\Models\Roster;
use Hdaklue\Porter\RoleFactory;
use Hdaklue\Porter\Roles\BaseRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ManageMemebersTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public RoleableEntity $roleableEntity;

    public function mount(RoleableEntity $roleableEntity)
    {
        $this->roleableEntity = $roleableEntity;
    }

    public function table(Table $table): Table
    {

        return $table
            ->records(fn () => $this->getParticipants())
            ->columns([
                TextColumn::make('assignable.name')
                    ->label('name'),
                TextColumn::make('assignable.username')
                    ->label('username'),
                TextColumn::make('role')
                    ->state(fn ($record) => ucfirst($record['role_key']->getName())),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('add_member')
                    ->visible(fn () => filamentUser()->can('manage', $this->roleableEntity))
                    ->form([
                        Select::make('member')
                            ->native(false)
                            ->required()
                            ->searchable()
                            ->options($this->getAssignableUsers()),
                        Select::make('role')
                            ->required()
                            ->options($this->getAllowedRoles()),
                    ])
                    ->action(function (array $data) {
                        try {
                            $role = RoleFactory::tryMake($data['role']);
                            $user = User::where('id', $data['member'])->first();
                            AddParticipant::run($this->roleableEntity, $user, $role);
                            Notification::make()
                                ->body(__('common.messages.operation_completed'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            logger()->error($e->getMessage());
                            Notification::make()
                                ->body(__('common.messages.operation_failed'))
                                ->danger()
                                ->send();

                        }

                    })
                    ->label('Add Memeber'),
            ])
            ->recordActions([
                Action::make('change_role')
                    ->visible(filamentUser()->can('manage', $this->roleableEntity))
                    ->label('Change Role'),
                Action::make('remove')
                    ->label('Remove Member')
                    ->color('danger')
                    ->action(function (Component $livewire, $record) {

                        $user = User::where('id', $record['assignable']['id'])->first();
                        RemoveParticipant::run($this->roleableEntity, $user);
                        $livewire->resetTable();
                    })
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.role.manage-memebers-table');
    }

    private function getParticipants()
    {
        return Porter::getParticipantsWithRoles($this->roleableEntity)
            ->reject(fn (Roster $item) => $item->assignable_id === filamentUser()->getKey())
            ->keyBy('assignable_id')->toArray();
        // dd($this->roleableEntity->getParticipantsWithRole()->keyBy('id')->toArray());
    }

    private function getAssignableUsers()
    {
        $this->roleableEntity->loadMissing('tenant');

        $tenant = $this->roleableEntity->tenant;
        $participantKeys = array_keys($this->getParticipants());
        $participantKeys[] = filamentUser()->getKey();

        return Porter::getParticipantsWithRoles($tenant)
            ->reject(fn (Roster $item) => in_array($item->assignable_id, $participantKeys))
            ->pluck('assignable.name', 'assignable.id')->toArray();
    }

    private function getAllowedRoles()
    {
        $currentRole = Porter::getRoleOn(filamentUser(), $this->roleableEntity);

        if (! empty($currentRole)) {
            return BaseRole::whereLowerThanOrEqual($currentRole);
        }

        return [];
    }
}
