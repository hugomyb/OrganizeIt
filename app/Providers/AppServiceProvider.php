<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
            Js::make('uikit-js', Vite::asset('resources/js/uikit.js')),
            Js::make('nestable-js', Vite::asset('resources/js/components/nestable.js')),
            Css::make('app-css', Vite::asset('resources/css/app.css')),
            Css::make('custom-css', Vite::asset('resources/css/custom.css')),
            Css::make('nestable-css', Vite::asset('resources/css/components/nestable.css')),
        ]);
    }
}
