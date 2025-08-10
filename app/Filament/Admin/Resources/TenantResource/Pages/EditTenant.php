<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use Filament\Schemas\Schema;
use App\Filament\Admin\Resources\TenantResource;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }

    //
    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),

        ];
    }
}
