<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Actions\Tenant\AddMember;
use App\Actions\Tenant\RemoveMember;
use Hdaklue\MargRbac\Enums\Role\RoleEnum;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

final class ParticipantRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('roleable_type')
            ->columns([
                TextColumn::make('model.name')
                    ->label('Name'),
                TextColumn::make('model.email')
                    ->label('Email'),
                TextColumn::make('role.name'),
                // ->getStateUsing(fn (RelationManager $livewire, $record) => RoleEnum::from($livewire->getOwnerRecord()->getParticipantRole($record->model)->name)->getLabel()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // @php-stan-ignore-next
                // Tables\Actions\CreateAction::make(),
                AttachAction::make()
                    ->modalWidth(Width::SixExtraLarge)
                    ->attachAnother(false)
                    ->label('Add Member')
                    ->icon('heroicon-s-user-plus')
                    ->schema(function (RelationManager $livewire) {
                        /** @var Tenant */
                        $record = $livewire->getOwnerRecord();
                        TenantResource::getAddMemberSchema($record);
                    })
                    ->action(function (RelationManager $livewire, $data) {
                        try {
                            $user = User::where('id', $data['members'])->first();
                            AddMember::run($livewire->getOwnerRecord(), $user, RoleEnum::from($data['system_roles']), $data['flows']);
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
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                DetachAction::make()
                    ->modalContent(new HtmlString('<span class="text-sm text-gray-300 dark:text-gray-500">CAUTION: this will remove user from all flows and tasks related to this Team</span>'))
                    ->label('Remove')
                    ->action(fn (RelationManager $livewire, $record) => RemoveMember::run($livewire->getOwnerRecord(),
                        $record->model,
                        filament()->auth()->user())),

                // ->after(fn (RelationManager $livewire, $record) => $livewire->getOwnerRecord()->removeParticipant($record)),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
                // Tables\Actions\BulkActionGroup::make([

                //     // Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
