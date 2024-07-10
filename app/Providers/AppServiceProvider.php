<?php

namespace App\Providers;

use App\Filament\Resources\ProjectResource\Pages\ShowProject;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
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
            Js::make('jquery-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'),
            Js::make('uikit-js', Vite::asset('resources/js/uikit.js')),
            Js::make('nestable-js', Vite::asset('resources/js/components/nestable.js')),
            Css::make('app-css', Vite::asset('resources/css/app.css')),
            Css::make('custom-css', Vite::asset('resources/css/custom.css')),
            Css::make('nestable-css', Vite::asset('resources/css/components/nestable.css')),
        ]);

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn(): View => view('components.meta-tags'),
            scopes: ShowProject::class,
        );
    }
}
