<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TenantResource\RelationManagers;

use App\Actions\Tenant\AddMember;
use App\Actions\Tenant\RemoveMember;
use App\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\TenantResource;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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

            ->recordTitleAttribute('roleable_type')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('role')
                    ->getStateUsing(fn (RelationManager $livewire, $record) => RoleEnum::from($livewire->getOwnerRecord()->rolesForUser($record)->first()->name)->getLabel()),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->modalWidth(MaxWidth::SixExtraLarge)
                    ->attachAnother(false)
                    ->label('Add Member')
                    ->icon('heroicon-s-user-plus')
                    ->form(fn (RelationManager $livewire) => TenantResource::getAddMemberSchema($livewire->getOwnerRecord()))

                    ->action(function (RelationManager $livewire, $data) {
                        try {
                            $user = User::where('id', $data['members'])->first();
                            AddMember::run($livewire->getOwnerRecord(), $user, RoleEnum::from($data['system_roles']), $data['flows']);
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
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->modalContent(new HtmlString('<span class="text-sm text-gray-300 dark:text-gray-500">CAUTION: this will remove user from all flows and tasks related to this Team</span>'))
                    ->label('Remove')
                    ->action(fn (RelationManager $livewire, $record) => RemoveMember::run($livewire->getOwnerRecord(), $record, filament()->auth()->user())),

                // ->after(fn (RelationManager $livewire, $record) => $livewire->getOwnerRecord()->removeParticipant($record)),
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
