<?php

declare(strict_types=1);

namespace App\Filament\Actions\Document;

use App\Contracts\Document\Documentable;
use Filament\Actions\Action;

final class ArchiveDocumentAction
{
    public static function make(Documentable $documentable)
    {
        return Action::make('archive')
            ->label('Archive')
            ->color('danger')
            ->requiresConfirmation();
    }
}
