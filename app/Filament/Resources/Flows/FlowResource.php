<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flows;

use App\Filament\Resources\Flows\Pages\CreateDocument;
use App\Filament\Resources\Flows\Pages\CreateFlow;
use App\Filament\Resources\Flows\Pages\FlowDocuments;
use App\Filament\Resources\Flows\Pages\ListFlows;
use App\Filament\Resources\Flows\Pages\ViewFlow;
use App\Models\Flow;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;

final class FlowResource extends Resource
{
    protected static ?string $model = Flow::class;

    protected static ?string $slug = 'f';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // public static function getRecordSubNavigation(Page $page): array
    // {
    //     return $page->generateNavigationItems([
    //         FlowPages::class,
    //     ]);
    // }

    public static function getPages(): array
    {
        return [
            'index' => ListFlows::route('/'),
            'create' => CreateFlow::route('/create'),
            'view' => ViewFlow::route('/{record}'),
            'pages' => FlowDocuments::route('{record}/ps'),
            'createDocument' => CreateDocument::route('{flow}/p/c'),
        ];
    }
}
