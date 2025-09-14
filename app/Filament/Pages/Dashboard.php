<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentInteractionsWidget;
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;

final class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = true;

    protected string $view = 'filament.pages.dashboard';

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
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
