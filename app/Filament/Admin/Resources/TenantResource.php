<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Actions\Tenant\AddMember;
use App\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Filament\Admin\Resources\TenantResource\RelationManagers\ParticipantRelationManager;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // public static function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([

    //         ]);
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('members'))
            ->columns([
                ToggleColumn::make('active')
                    ->label('Status'),
                TextColumn::make('name'),
                ImageColumn::make('members')
                    ->getStateUsing(fn ($record) => $record->members->pluck('avatar'))
                    ->circular()
                    ->stacked(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('add')
                    ->label('Add Member')
                    ->icon('heroicon-s-user-plus')
                    ->form(
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
                        } catch (\Exception $exception) {
                            Notification::make()
                                ->body('Something went wrong')
                                ->danger()
                                ->color('danger')
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
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
                        ->options(User::query()->notMemberOf($record)->get()->mapWithKeys(fn ($record) => [$record->id => "{$record->name} - {$record->email}"])),
                    Select::make('system_roles')
                        ->options(RoleEnum::class)
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
            'index' => Pages\ListTenants::route('/'),
            // 'create' => Pages\CreateTenant::route('/create'),

            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
