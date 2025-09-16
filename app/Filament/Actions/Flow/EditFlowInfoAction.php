<?php

namespace App\Filament\Actions\Flow;

use App\Models\Document;
use App\Models\Flow;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class EditFlowInfoAction
{
    public static function make(Flow $record)
    {
        return EditAction::make('edit')
            ->visible(filamentUser()->can('update', $record))
            ->record($record)
            ->fillForm([
                'title' => $record->getAttribute('title'),
                'description' => $record->getAttribute('description'),
            ])
            ->schema([
                TextInput::make('title'),
                Textarea::make('description'),
            ]);
    }
}
