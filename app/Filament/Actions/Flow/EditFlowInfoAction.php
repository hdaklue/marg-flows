<?php

declare(strict_types=1);

namespace App\Filament\Actions\Flow;

use App\Models\Flow;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class EditFlowInfoAction
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
