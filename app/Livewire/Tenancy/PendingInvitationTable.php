<?php

declare(strict_types=1);

namespace App\Livewire\Tenancy;

use App\Models\MemberInvitation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

final class PendingInvitationTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => MemberInvitation::forTenant(filamentTenant()))
            ->columns([
                TextColumn::make('receiver_email')->label('Email'),
                TextColumn::make('role_key')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->label('Created At'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    #[On('PendingInvitationTable::refresh')]
    public function reloadTab(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.tenancy.pending-invitation-table');
    }
}
