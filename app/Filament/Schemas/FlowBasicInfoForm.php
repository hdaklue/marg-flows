<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class FlowBasicInfoForm
{
    public string $title;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->state(fn() => $this->title),
        ]);
    }
}
