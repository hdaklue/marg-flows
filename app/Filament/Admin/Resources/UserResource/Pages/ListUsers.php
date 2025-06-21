<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Actions\Invitation\SendInvitation;
use App\DTOs\Invitation\InvitationDTO;
use App\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
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
                    TextInput::make('email'),
                    Repeater::make('tenents')
                        ->reorderable(false)
                        ->columns(2)
                        ->schema([
                            Select::make('tenant_id')
                                ->options(fn () => Tenant::pluck('name', 'id'))
                                ->native(false)
                                ->label('Assign to')
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->live(onBlur: true)
                                ->searchable(),
                            Select::make('role_id')
                                ->label('Role')
                                ->options(fn (Get $get) => Role::where('tenant_id', $get('tenant_id'))->whereNotIn('name', [RoleEnum::SUPER_ADMIN->value, RoleEnum::TENANT_ADMIN->value])->get()->mapWithKeys(fn ($role) => [$role->id => RoleEnum::from($role->name)->getLabel()])),
                        ]),
                ])->action(function ($data) {
                    $user = filament()->auth()->user()->toArray();
                    $dto = new InvitationDTO([
                        'email' => $data['email'],
                        'role_data' => $data['tenents'],
                        'sender' => $user,
                    ]);
                    SendInvitation::run($dto);
                }),
        ];
    }
}
