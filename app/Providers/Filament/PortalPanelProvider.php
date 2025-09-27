<?php

declare(strict_types=1);

namespace App\Providers\Filament;

// Language switch plugin removed - using custom solution
use App\Filament\Auth\Login;
use App\Filament\Auth\Register;
use App\Filament\Pages\Tenancy\ManageMembers;
use App\Filament\Pages\UserSettings;
use App\Filament\Widgets\RecentInteractionsWidget;
use App\Http\Middleware\Filament\ConfigureDateTimePickers;
use App\Http\Middleware\SetLocale;
use App\Models\Tenant;
use Asmit\ResizedColumn\ResizedColumnPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
            ->path('/app')
            ->login(Login::class)
            ->passwordReset()
            ->registration(Register::class)
            ->databaseNotificationsPolling(fn () => app()->isProduction() ? '60s' : '90s')
            ->tenant(Tenant::class)
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->spa()
            ->topNavigation(false)
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs(false)
            ->viteTheme('resources/css/filament/portal/theme.css')
            ->databaseNotifications()
            ->unsavedChangesAlerts(false)
            ->plugins([
                // Custom language switching will be added via render hooks
            ])
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn (): string => Blade::render(
                '<x-language-switch />',
            ))
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): string => Blade::render('<x-language-switch />'),
                scopes: [Login::class, Register::class],
            )
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => Blade::render(
                '@livewire(\'wire-elements-modal\')',
            ))
            ->userMenuItems([
                Action::make('settings')
                    ->label(fn (): string => __('auth.profile.title'))
                    ->url(fn (): string => UserSettings::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->tenantProfile(ManageMembers::class)
            ->tenantMenuItems([
                //                Action::make('members')
                //                    ->label('Members')
                //                    ->url(fn (): string => ManageMembers::getUrl())
                //                    ->icon('heroicon-o-users'),
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages',
            )
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets',
            )
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                RecentInteractionsWidget::class,
            ])
            ->plugins([
                ResizedColumnPlugin::make(),
            ])
            ->pages([])
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
