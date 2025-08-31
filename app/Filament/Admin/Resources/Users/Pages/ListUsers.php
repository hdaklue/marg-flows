<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Actions\Invitation\InviteMember;
use App\DTOs\Invitation\InvitationDTO;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\Timezone;
use Exception;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Log;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('app.invite'))
                ->modal()
                ->modalAutofocus()
                ->modalHeading(__('app.invite'))
                ->createAnother(false)
                ->schema([
                    TextInput::make('name')
                        ->label(__('app.name'))
                        ->required(),
                    TextInput::make('email')
                        ->label(__('app.email'))
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
                                ->label(__('app.assign'))
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->live(onBlur: true),
                            Select::make('role_id')
                                ->label(__('app.role'))
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
