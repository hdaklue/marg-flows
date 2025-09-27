<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Livewire\Participants\TenantMembersTable;
use BackedEnum;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class ManageMembers extends EditTenantProfile
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-users';

    public static function getLabel(): string
    {
        return 'Settings';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Livewire::make(TenantMembersTable::class, ['tenant' => $this->tenant]),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTenant()->participants()->with('assignable'))
            ->columns([
                Tables\Columns\TextColumn::make('assignable.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignable.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
