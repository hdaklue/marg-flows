<?php

declare(strict_types=1);

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
            Actions\CreateAction::make('create')
                ->createAnother(false)
                ->form([
                    TextInput::make('name')
                        ->required(),
                    Repeater::make('members')
                        ->schema([
                            Select::make('name')
                                ->options(fn () => User::get()->mapWithKeys(fn ($user) => [$user->id => "{$user->name} - {$user->email}"]))
                                ->required()
                                ->searchable(true)
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} - {$record->email}")
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->native(false),
                            Select::make('role')
                                ->options(RoleEnum::class)
                                ->searchable(true)
                                ->required()
                                ->native(false),
                        ])
                        ->reorderable(false)
                        ->columns(2),
                ])->action(function (array $data): void {

                    try {
                        CreateTenant::run($data, filament()->auth()->user());
                    } catch (\Exception $e) {
                        throw_unless(app()->isProduction(), $e);
                        Notification::make()
                            ->body('Something went wrong')
                            ->danger()
                            ->send();
                    }
                })->after(function () {
                    Notification::make()
                        ->body('Tenant created')
                        ->success()
                        ->send();
                }),
        ];
    }
}
