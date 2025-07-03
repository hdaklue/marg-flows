<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\Account\AccountType;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $label = 'Member';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['latestLogin', 'receivedInvitation']))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('account_type')
                    ->color(fn ($state) => AccountType::from($state)->getColor())
                    ->label('Account Type')
                    ->formatStateUsing(fn ($state) => \ucwords($state))
                    ->badge(),
                TextColumn::make('latestLogin.created_at')
                    ->dateTime('j F Y g:i A')

                    ->label('Last Login'),
                TextColumn::make('receivedInvitation.sender.name')
                    ->label('Invited By'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
