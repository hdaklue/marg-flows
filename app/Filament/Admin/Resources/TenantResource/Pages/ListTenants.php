<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Actions\Tenant\CreateTenant;
use App\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\TenantResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->form([
                    TextInput::make('name')
                        ->required(),
                    Repeater::make('members')
                        ->schema([
                            Select::make('name')
                                ->options(fn () => User::get()->pluck('name', 'id'))
                                ->required()
                                ->searchable(true)
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->native(false),
                            Select::make('role')
                                ->options(RoleEnum::class)
                                ->searchable(true)
                                ->required()
                                ->native(false),
                        ])
                        ->columns(2),
                ])->action(function (array $data): void {

                    try {

                        CreateTenant::run($data);
                        Notification::make()
                            ->body('Tenant created')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        if (! app()->isProduction()) {
                            throw $e;
                        }
                        Notification::make()
                            ->body('Something went wrong')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
