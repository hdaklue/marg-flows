<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use App\Filament\Resources\FlowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\ActionSize;

final class ListFlows extends ListRecords
{
    protected static string $resource = FlowResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->size(ActionSize::ExtraSmall)
                ->outlined(),

        ];
    }
}
