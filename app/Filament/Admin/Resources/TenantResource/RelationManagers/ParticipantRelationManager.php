<?php

namespace App\Filament\Admin\Resources\TenantResource\RelationManagers;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParticipantRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->recordTitleAttribute('roleable_type')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                TextColumn::make('role')
                    ->getStateUsing(fn ($record) => $record->roles->first()->name),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                // Tables\Actions\AttachAction::make()
                //     ->form([
                //         Select::make('members')
                //             ->required()
                //             ->options(fn (RelationManager $livewire) => User::notMemberOf($livewire->getOwnerRecord())->pluck('name', 'id'))
                //             ->searchable(),
                //     ]),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->after(fn (RelationManager $livewire, $record) => $livewire->getOwnerRecord()->removeParticipant($record)),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
                // Tables\Actions\BulkActionGroup::make([

                //     // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
