<?php

declare(strict_types=1);

namespace App\Filament\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class ParticipantsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('hello'),
        ]);
    }
}
