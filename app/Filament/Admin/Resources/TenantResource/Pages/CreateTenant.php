<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required(),
            Repeater::make('participants'),
        ]);
    }
}
