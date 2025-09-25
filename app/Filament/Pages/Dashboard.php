<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentInteractionsWidget;
use App\Polishers\UserPolisher;
use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function getHeading(): Htmlable|string
    {
        return 'Hi, ' . UserPolisher::polishUserName(filamentUser());
    }
}
