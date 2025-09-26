<?php

declare(strict_types=1);

namespace App\Livewire\Participants\Actions;

use App\Actions\Roleable\RemoveParticipant;
use App\Models\User;
use Filament\Actions\Action;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Livewire\Component;

final class RemoveMemberAction
{
    public static function make(RoleableEntity $roleableEntity): Action
    {
        return Action::make('remove')
            ->icon('heroicon-s-trash')
            ->iconButton()
            ->label(__('participants.actions.remove_member'))
            ->visible(filamentUser()->can('manage', $roleableEntity))
            ->color('danger')
            ->action(function (Component $livewire, $record) use ($roleableEntity) {
                $user = User::where('id', $record['id'])->first();
                RemoveParticipant::run($roleableEntity, $user);
                $livewire->resetTable();
            })
            ->requiresConfirmation()
            ->modalHeading(__('participants.confirmations.remove_member'));
    }
}
