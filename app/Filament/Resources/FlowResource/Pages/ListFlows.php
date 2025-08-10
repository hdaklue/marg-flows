<?php

declare(strict_types=1);

namespace App\Filament\Resources\FlowResource\Pages;

use Filament\Support\Enums\Width;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Size;
use App\Filament\Resources\FlowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFlows extends ListRecords
{
    protected static string $resource = FlowResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->size(Size::ExtraSmall)
                ->outlined(),

        ];
    }
}
