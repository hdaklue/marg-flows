<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Actions\Invitation\InviteMember;
use App\DTOs\Invitation\InvitationDTO;
use App\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Tenant;
use Exception;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Invite Member')
                ->modal()
                ->modalAutofocus()
                ->modalHeading('Invite new member')
                ->createAnother(false)
                ->form([
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('email')
                        ->required()
                        ->live(onBlur: true)
                        ->validationMessages([
                            'unique' => 'The :attribute has already been registered.',
                        ])
                        ->email()
                        ->unique('users'),

                    Repeater::make('tenents')
                        ->reorderable(false)
                        ->columns(2)
                        ->schema([
                            Select::make('tenant_id')
                                ->options(fn () => Tenant::pluck('name', 'id'))
                                ->native(false)
                                ->required()
                                ->label('Assign to')
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->live(onBlur: true)
                                ->searchable(),
                            Select::make('role_id')
                                ->required()
                                ->label('Role')
                                ->options(fn (Get $get) => Role::where('tenant_id', $get('tenant_id'))->whereNotIn('name', [RoleEnum::SUPER_ADMIN->value, RoleEnum::TENANT_ADMIN->value])->get()->mapWithKeys(fn ($role) => [$role->id => RoleEnum::from($role->name)->getLabel()])),
                        ]),
                ])->action(function ($data) {
                    try {
                        $user = filament()->auth()->user()->toArray();
                        $dto = new InvitationDTO([
                            'email' => $data['email'],
                            'role_data' => $data['tenents'],
                            'name' => $data['name'],
                            'sender' => $user,
                        ]);

                        InviteMember::run($dto);
                        Notification::make()
                            ->body('Invitation sent successfully')
                            ->success()
                            ->color('success')
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->body('Something went wrong!')
                            ->color('danger')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
