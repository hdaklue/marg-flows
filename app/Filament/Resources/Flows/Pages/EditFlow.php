<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows\Pages;

use App\Filament\Resources\Flows\FlowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditFlow extends EditRecord
{
    protected static string $resource = FlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
