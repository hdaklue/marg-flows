<?php

declare(strict_types=1);

namespace App\Filament\Tables;

use App\Actions\Tenant\SwitchTenant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class UserTenant
{
    public static function configure(Table $table): Table
    {
        return $table
            ->records(
                fn() => filamentUser()
                    ->getAssignedTenants()
                    ->keyBy('id')
                    ->toArray(),
            )
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('switch')
                    ->hidden(
                        fn($record) => (
                            $record['id'] === filamentTenant()->getKey()
                        ),
                    )
                    ->action(function ($record) {
                        SwitchTenant::run(filamentUser(), $record['id']);

                        // Force page reload to new tenant context
                    }),
                // Action::make('leave')
                //     ->requiresConfirmation()
                //     ->color('danger')
                //     ->action(fn ($record) => dd($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
