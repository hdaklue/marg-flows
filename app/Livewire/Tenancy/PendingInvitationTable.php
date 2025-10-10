<?php

declare(strict_types=1);

namespace App\Livewire\Tenancy;

use App\Actions\Invitation\RevokeInvitaion;
use App\Models\MemberInvitation;
use App\Polishers\InvitationPolisher;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Enums\ActionStatus;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

final class PendingInvitationTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /**
     * @throws Throwable
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => MemberInvitation::forTenant(filamentTenant())->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('receiver_email')->label('Email'),
                TextColumn::make('role_key')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->badge()
                    ->color('gray')
                    ->label('Role'),
                TextColumn::make('created_at')
                    ->label('Sent at')
                    ->timezone(filamentUser()->getTimezone())
                    ->date(),
                TextColumn::make('status')
                    ->badge()
                    ->color('secondary')

                    ->state(fn ($record) => InvitationPolisher::status($record)),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-trash')
                    ->visible(fn ($record) => InvitationPolisher::status($record) === 'sent')
                    ->iconButton()
                    ->label('Delete')
                    ->action(fn (MemberInvitation $record) => RevokeInvitaion::run($record->getKey()))
                    ->after(function ($action) {
                        match ($action->getStatus()) {
                            ActionStatus::Success => Notification::make()->success()->body(__('common.messages.operation_completed'))->send(),
                            ActionStatus::Failure => Notification::make()->danger()->body(__('common.messages.operation_failed'))->send(),
                        };

                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    #[On('PendingInvitationTable::refresh')]
    public function reloadTab(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.tenancy.pending-invitation-table');
    }
}
