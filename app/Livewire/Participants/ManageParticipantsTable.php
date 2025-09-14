<?php

declare(strict_types=1);

namespace App\Livewire\Participants;

use App\Collections\ParticipantsCollection;
use App\Filament\Forms\Components\Resuable\RoleSelect;
use App\Livewire\Participants\Actions\AddMemberAction;
use App\Livewire\Participants\Actions\RemoveMemberAction;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Facades\Porter;
use Hdaklue\Porter\Models\Roster;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ManageParticipantsTable extends Component implements HasActions, HasSchemas, HasTable
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
                Split::make([
                    ImageColumn::make('avatarUrl')->circular()->grow(false),
                    Stack::make([
                        TextColumn::make('name')->weight(FontWeight::Bold)->label('name'),
                        TextColumn::make('username')
                            ->size(TextSize::ExtraSmall)
                            ->fontFamily(FontFamily::Mono)
                            ->formatStateUsing(fn ($state) => "@{$state}")
                            ->label('username'),
                    ])->grow(),
                    Split::make([
                        TextColumn::make('role')
                            ->alignEnd()
                            ->badge()
                            // ->formatStateUsing(fn($state) => dd($state)),
                            ->state(fn ($record) => ucfirst($record['role']['name'])),
                    ]),
                ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AddMemberAction::make(
                    $this->roleableEntity,
                    filamentUser(),
                    $this->getAssignableUsers(),
                ),
            ])
            ->recordActions([
                Action::make('change_role')
                    ->icon('heroicon-c-queue-list')
                    ->iconButton()
                    ->visible(filamentUser()->can('manage', $this->roleableEntity))
                    ->form([
                        RoleSelect::make('role', $this->roleableEntity, filamentUser()),
                    ])
                    ->color('gray')
                    ->action(fn ($record, array $data) => $this->doChangeRole(
                        $record['id'],
                        $data['role'],
                    ))
                    ->label('Change Role'),
                RemoveMemberAction::make($this->roleableEntity),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.participants.manage-participants-table');
    }

    private function doChangeRole(string|int $targetId, string $role)
    {
        try {
            $targetEntity = User::query()->where('id', $targetId)->firstOrFail();
            Porter::changeRoleOn($targetEntity, $this->roleableEntity, $role);
            $this->resetTable();
        } catch (Exception $e) {
            logger()->error('Error changing role', [
                'actor' => filamentTenant(),
                'targetEntityId' => $targetId,
                'role' => $role,
                $e->getMessage(),
            ]);
            Notification::make()
                ->body(__('common.messages.operation_failed'))
                ->danger()
                ->send();
        }
    }

    private function getParticipants()
    {
        return (new ParticipantsCollection(
            Porter::getParticipantsWithRoles($this->roleableEntity)
                ->reject(fn (Roster $item) => $item->assignable_id === filamentUser()->getKey())
                ->keyBy('assignable_id'),
        ))
            ->asDtoCollection()
            ->map(fn ($item) => $item->toArray())
            ->toArray();
    }

    private function getAssignableUsers()
    {
        $this->roleableEntity->loadMissing('tenant');

        $tenant = $this->roleableEntity->tenant;
        $participantKeys = array_keys($this->getParticipants());
        $participantKeys[] = filamentUser()->getKey();

        return Porter::getParticipantsWithRoles($tenant)
            ->reject(fn (Roster $item) => in_array($item->assignable_id, $participantKeys))
            ->pluck('assignable.name', 'assignable.id')
            ->toArray();
    }
}
