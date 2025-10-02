<?php

declare(strict_types=1);

namespace App\Filament\Actions\Participants;

use App\Actions\Invitation\InviteMember;
use App\DTOs\Invitation\InvitationDTO;
use App\Filament\Forms\Components\Resuable\RoleSelect;
use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Hdaklue\Porter\Contracts\RoleableEntity;
use Hdaklue\Porter\Rules\NotAssignedTo;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final readonly class InviteMemberAction
{
    /**
     * @throws Throwable
     */
    public static function make(Tenant|Model $roleableEntity): Action
    {
        return Action::make('invite_member')
            ->label(__('participants.actions.invite_to_team'))
            ->outlined()
            ->color('gray')
            ->action(function (array $data) {
                try {
                    //                    $dto = InvitationDTO::fromArray([
                    //                        'email' => $data['email'],
                    //                        'role_key' => $data['role'],
                    //                        'sender' => filamentUser(),
                    //                        'tenant' => filamentTenant(),
                    //                    ]);
                    InviteMember::run(filamentUser(), filamentTenant(), $data['role'], $data['email']);

                    Notification::make()
                        ->body(__('common.messages.operation_completed'))
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    logger()->error($e);
                    Notification::make()
                        ->body(__('common.messages.operation_failed'))
                        ->danger()
                        ->send();
                }
            })
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
                RoleSelect::make('role', filamentTenant(), filamentUser()),
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
