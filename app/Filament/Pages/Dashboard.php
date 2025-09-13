<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentInteractionsWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static null|int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return 'heroicon-o-home';
    }

    public function getWidgets(): array
    {
        return [
            RecentInteractionsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getTitle(): Htmlable|string
    {
        return 'Hi, ' . str(filamentUser()->name)->beforeLast(' ')->toString();
    }
}
