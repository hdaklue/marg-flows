<?php

declare(strict_types=1);

namespace App\Livewire\Participants\Actions;

use App\Actions\Roleable\AddParticipant;
use App\Filament\Forms\Components\Resuable\RoleSelect;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\RoleFactory;

final class AddMemberAction
{
    public static function make(
        RoleableEntity $roleableEntity,
        AssignableEntity $actor,
        array $assignableEntities,
    ): Action {
        return Action::make('add_member')
            ->visible(fn() => filamentUser()->can('manage', $roleableEntity))
            ->form([
                Select::make('member')
                    ->native(false)
                    ->required()
                    ->searchable()
                    ->options($assignableEntities),
                RoleSelect::make('role', $roleableEntity, $actor),
            ])
            ->action(function (array $data) use ($roleableEntity) {
                try {
                    $role = RoleFactory::tryMake($data['role']);
                    $user = User::where('id', $data['member'])->first();
                    AddParticipant::run($roleableEntity, $user, $role);
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
            ->label('Add Memeber');
    }
}
