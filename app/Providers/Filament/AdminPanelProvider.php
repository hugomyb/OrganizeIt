<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfilePage;
use App\Filament\Resources\ProjectResource\Pages\ShowProject;
use App\Filament\Resources\ProjectResource\Widgets\RecentProjects;
use App\Filament\Widgets\LatestTasksWidget;
use App\Filament\Widgets\StatsAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Hugomyb\FilamentErrorMailer\FilamentErrorMailerPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->spa()
            ->path('/')
            ->favicon(asset('/img/favicon.png', true))
            ->brandLogo(asset('/img/logo-oi.png', true))
            ->darkModeBrandLogo(asset('/img/logo-oi-dark.png', true))
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsAdmin::class,
                RecentProjects::class,
                LatestTasksWidget::class
            ])
            ->sidebarCollapsibleOnDesktop()
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => auth()->user()->name)
                    ->url(fn(): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle'),
                'settings' => MenuItem::make()
                    ->label(__('profile.settings'))
                    ->url(fn(): string => EditProfilePage::getUrl() . '#settings')
                    ->icon('heroicon-m-cog'),
            ])
            ->plugins([
                FilamentErrorMailerPlugin::make()
            ])
            ->renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                fn () => Blade::render('@livewire(\'searchbar\')'),
                scopes: [ShowProject::class])
            ->renderHook(PanelsRenderHook::BODY_END,
                fn () => Blade::render('@livewire(\'global-searchbar\')'));
    }
}
