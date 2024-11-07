<?php

namespace App\Providers;

use App\Filament\Resources\ProjectResource\Pages\ShowProject;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Stichoza\GoogleTranslate\GoogleTranslate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleTranslate::class, function ($app) {
            return new GoogleTranslate();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') == 'production') {
            URL::forceScheme('https');
        };

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['fr', 'en']);
        });

        FilamentAsset::register([
            Css::make('app-css', Vite::asset('resources/css/app.css')),
            Js::make('app-js', Vite::asset('resources/js/app.js')),
            Css::make('custom-css', Vite::asset('resources/css/custom.css')),

            Js::make('tui-calendar-js', 'https://uicdn.toast.com/calendar/latest/toastui-calendar.min.js'),
            Css::make('tui-calendar-css', 'https://uicdn.toast.com/calendar/latest/toastui-calendar.min.css'),
            AlpineComponent::make('calendar-component', __DIR__ . '/../../resources/js/dist/components/calendar-component.js'),
        ]);

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn(): View => view('components.meta-tags'),
            scopes: ShowProject::class,
        );

        Model::preventLazyLoading(!app()->isProduction());
    }
}
