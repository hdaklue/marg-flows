<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use Filament\Schemas\Schema;
use App\Filament\Admin\Resources\TenantResource;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),
            Repeater::make('participants'),
        ]);
    }
}
