<?php

declare(strict_types=1);

namespace App\Livewire\Participants;

use App\Collections\ParticipantsCollection;
use App\Filament\Actions\Participants\InviteMemberAction;
use App\Filament\Forms\Components\Resuable\RoleSelect;
use App\Livewire\Participants\Actions\AddMemberAction;
use App\Livewire\Participants\Actions\RemoveMemberAction;
use App\Models\Tenant;
use App\Models\User;
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
use Hdaklue\Porter\Facades\Porter;
use Hdaklue\Porter\RoleFactory;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

final class TenantMembersTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public Tenant $tenant;

    public function mount(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * @throws Throwable
     */
    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getTenantMembers())
            ->columns([
                Split::make([
                    ImageColumn::make('assignable.avatarUrl')->circular()->grow(false),
                    Stack::make([
                        TextColumn::make('assignable.name')
                            ->weight(FontWeight::Bold)->label(__('participants.labels.name')),
                        TextColumn::make('assignable.username')
                            ->size(TextSize::ExtraSmall)
                            ->fontFamily(FontFamily::Mono)
                            ->formatStateUsing(fn ($state) => "@{$state}")
                            ->label(__('participants.labels.username')),
                    ])->grow(),
                    Split::make([
                        TextColumn::make('role')
                            ->alignEnd()
                            ->badge()
                            ->color('gray')
                            ->state(fn ($record) => $record->role_key->getLabel()),
                    ]),
                ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //                AddMemberAction::make(
                //                    $this->tenant,
                //                    filamentUser(),
                //                    $this->getAssignableUsers(),
                //                )->outlined(),
                InviteMemberAction::make($this->tenant)
                    ->color('primary'),
            ])
            ->recordActions([
                Action::make('change_role')
                    ->icon('heroicon-c-queue-list')
                    ->iconButton()
                    ->visible(fn ($record) => $this->canManageTenant() && $record->assignable_id !== filamentUser()->getKey())
                    ->schema([
                        RoleSelect::make('role', $this->tenant, filamentUser()),
                    ])
                    ->color('gray')
                    ->action(fn ($record, array $data) => $this->doChangeRole(
                        $record['id'],
                        $data['role'],
                    ))
                    ->label(__('participants.actions.change_role')),
                RemoveMemberAction::make($this->tenant)
                    ->visible(fn ($record) => $this->canManageTenant() && $record->assignable_id !== filamentUser()->getKey()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.participants.tenant-members-table');
    }

    private function doChangeRole(string|int $targetId, string $role): void
    {
        try {
            $targetEntity = User::query()->where('id', $targetId)->firstOrFail();
            Porter::changeRoleOn($targetEntity, $this->tenant, $role);
            $this->resetTable();
        } catch (Throwable $e) {
            logger()->error('Error changing tenant member role', [
                'actor' => filamentUser(),
                'tenant' => $this->tenant->id,
                'targetEntityId' => $targetId,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            Notification::make()
                ->body(__('participants.messages.operation_failed'))
                ->danger()
                ->send();
        }
    }

    private function getTenantMembers()
    {
        return Porter::getParticipantsWithRoles($this->tenant)
//            ->reject(fn ($item) => $item->assignable_id === filamentUser()->getKey())
            ->keyBy('assignable_id');
        //        return (new ParticipantsCollection(
        //
        //        ))
        //            ->asDtoCollection()
        //            ->map(fn ($item) => $item->toArray())
        //            ->toArray();
    }

    /**
     * @throws Throwable
     */
    //    private function getAssignableUsers()
    //    {
    //        $participantKeys = array_keys($this->getTenantMembers());
    //
    //        // Get all users not already members of this tenant
    //        return User::query()
    //            ->whereNotIn('id', $participantKeys)
    //            ->pluck('name', 'id')
    //            ->toArray();
    //    }

    /**
     * @throws Throwable
     */
    private function canManageTenant(): bool
    {
        return Porter::hasRoleOn(filamentUser(), $this->tenant, RoleFactory::admin());
    }
}
