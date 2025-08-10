<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Utilities\Get;
use App\Actions\Invitation\InviteMember;
use App\DTOs\Invitation\InvitationDTO;
use App\Filament\Admin\Resources\UserResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\Timezone;
use Exception;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Invite Member')
                ->modal()
                ->modalAutofocus()
                ->modalHeading('Invite new member')
                ->createAnother(false)
                ->schema([
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
                    Select::make('timezone')
                        ->options(Timezone::getTimezonesAsSelectList())
                        ->searchable()
                        ->required()
                        ->preload()
                        ->native(false),

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
                                ->live(onBlur: true),
                            Select::make('role_id')
                                ->label('Role')
                                ->required()
                                ->options(fn (Get $get) => Role::where('tenant_id', $get('tenant_id'))->pluck('name', 'id'),
                                )
                                ->live(),
                        ]),
                ])->action(function ($data) {
                    try {

                        $user = filament()->auth()->user()->toArray();
                        $dto = new InvitationDTO([
                            'email' => $data['email'],
                            'role_data' => $data['tenents'],
                            'name' => $data['name'],
                            'sender' => $user,
                            'timezone' => $data['timezone'],
                        ]);

                        InviteMember::run($dto);
                        Notification::make()
                            ->body('Invitation sent successfully')
                            ->success()
                            ->color('success')
                            ->send();
                    } catch (Exception $e) {
                        Log::error($e->getMessage());
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
