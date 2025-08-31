<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants;

use App\Actions\Tenant\AddMember;
use App\Enums\Account\AccountType;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\RelationManagers\ParticipantRelationManager;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    // protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('app.tenants');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.tenants');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.tenants');
    }

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([

    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('participants'))
            ->columns([
                ToggleColumn::make('active')
                    ->label(__('app.status')),
                TextColumn::make('name')
                    ->label(__('app.name')),
                ImageColumn::make('avatar')
                    ->getStateUsing(fn ($record) => $record->participants->pluck('model')->map(fn ($model) => $model->getAvatarUrl())->toArray())
                    ->circular()
                    ->stacked(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('add')
                    ->label(__('app.invite'))
                    ->icon('heroicon-s-user-plus')
                    ->schema(
                        fn ($record) => TenantResource::getAddMemberSchema($record),
                    )->action(function (array $data, Tenant $record): void {
                        try {

                            $user = User::where('id', $data['members'])->first();
                            AddMember::run($record, $user, RoleEnum::from($data['system_roles']), $data['flows']);
                            Notification::make()
                                ->body('Participant added')
                                ->success()
                                ->color('success')
                                ->send();
                        } catch (Exception $exception) {
                            Notification::make()
                                ->body('Something went wrong')
                                ->danger()
                                ->color('danger')
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ParticipantRelationManager::class,
        ];
    }

    public static function getAddMemberSchema(Tenant $record): array
    {

        return [
            Grid::make()
                ->schema([
                    Select::make('members')
                        ->required()
                        ->searchable(true)
                        ->native(false)
                        ->options(User::query()->notAssignedTo($record)->get()->mapWithKeys(fn ($record) => [$record->id => "{$record->name} - {$record->email}"])),
                    Select::make('system_roles')
                        ->options(function () {

                            $userRole = match (filamentUser()->account_type) {
                                AccountType::ADMIN->value => RoleEnum::ADMIN->value,
                                AccountType::MANAGER->value => RoleEnum::MANAGER->value,
                                default => throw new Exception('Invalid account type'),
                            };

                            return RoleEnum::whereLowerThanOrEqual(RoleEnum::from($userRole))->toArray();
                        })
                        ->searchable(true)
                        ->required()
                        ->native(false),
                    CheckboxList::make('flows')
                        ->columnSpanFull()
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(3)
                        ->options(Flow::byTenant($record)->pluck('title', 'id')),
                ])->columns(2),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            // 'create' => Pages\CreateTenant::route('/create'),

            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
