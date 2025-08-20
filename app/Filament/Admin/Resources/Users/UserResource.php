<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Enums\Account\AccountType;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use function ucwords;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $label = 'Member';

    public static function getModelLabel(): string
    {
        return __('app.users');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.users');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['latestLogin', 'receivedInvitation.sender']))
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.name')),
                TextColumn::make('email')
                    ->label(__('app.email')),
                TextColumn::make('account_type')
                    ->color(fn ($state) => AccountType::from($state)->getColor())
                    ->label(__('app.account_type'))
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->badge(),
                TextColumn::make('latestLogin.created_at')
                    ->dateTime('j F Y g:i A')
                    ->label(__('app.last_login')),
                TextColumn::make('receivedInvitation.sender.name')
                    ->label(__('app.invited_by')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
