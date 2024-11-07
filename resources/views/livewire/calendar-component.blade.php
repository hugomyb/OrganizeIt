<div
    x-ignore
    ax-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getScriptSrc('tui-calendar-js') }}"
    x-load-css-src="{{ \Filament\Support\Facades\FilamentAsset::getStyleHref('tui-calendar-css') }}"
    ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('calendar-component') }}"
    x-data="calendarComponent({
        wire: $wire,
    })">

    <div id="calendar" style="height: 800px;"></div>
</div>
