<?php

declare(strict_types=1);

namespace App\Filament\Actions\Participants;

use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Rules\NotAssignedTo;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final readonly class InviteMemberAction
{
    public static function make(Tenant|Model $roleableEntity): Action
    {
        return Action::make('invite_member')
            ->label(__('participants.actions.invite_to_team'))
            ->outlined()
            ->color('gray')
            ->action(fn () => logger()->info('hello'))
            ->schema([
                TextInput::make('email')
                    ->rules(function ($state) use ($roleableEntity) {
                        $user = User::where('email', $state)->first();
                        if ($user) {
                            return [
                                new NotAssignedTo($user, $roleableEntity),
                            ];
                        }

                        return [];
                    })
                    ->label(__('participants.labels.email'))
                    ->placeholder(__('participants.placeholders.enter_email'))
                    ->required()
                    ->email(),
            ])->visible(fn () => self::canManageRoleableEntity($roleableEntity));
    }

    /**
     * @throws Throwable
     */
    private static function canManageRoleableEntity(RoleableEntity $roleableEntity): bool
    {
        return filamentUser()->can('manage', $roleableEntity);
    }
}
