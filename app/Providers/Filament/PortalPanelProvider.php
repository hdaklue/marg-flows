<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Auth\Register;
use App\Filament\Pages\UserSettings;
use App\Http\Middleware\Filament\ConfigureDateTimePickers;
use App\Http\Middleware\SetLocale;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
// Language switch plugin removed - using custom solution
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('portal')
            ->path('/portal')
            ->login(Login::class)
            ->passwordReset()
            ->registration(Register::class)
            ->databaseNotificationsPolling(fn () => app()->isProduction() ? '60s' : '90s')
            ->tenant(Tenant::class, ownershipRelationship: 'tenant')
            ->colors([
                'primary' => Color::Sky,
            ])
            ->topNavigation(true)
            ->viteTheme('resources/css/filament/portal/theme.css')
            ->databaseNotifications()
            ->unsavedChangesAlerts(false)
            ->plugins([
                // Custom language switching will be added via render hooks
            ])
            ->maxContentWidth(Width::Full)
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('<x-language-switch />'),
            )
            // ->renderHook(
            //     PanelsRenderHook::SIMPLE_LAYOUT_START,
            //     fn (): string => '<div class="w-full min-h-screen lg:grid lg:grid-cols-2"><div class="flex flex-col justify-center flex-1 px-4 py-12 sm:px-6 lg:px-8"><div class="w-full max-w-sm mx-auto">',
            //     scopes: [Login::class, Register::class],
            // )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): string => Blade::render('<x-language-switch />'),
                scopes: [Login::class, Register::class],
            )
            ->userMenuItems([
                Action::make('settings')
                    ->label(fn (): string => __('auth.profile.title'))
                    ->url(fn (): string => UserSettings::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ConfigureDateTimePickers::class,
            ]);
    }
}
