@php
    $debounce = filament()->getGlobalSearchDebounce();
    $keyBindings = filament()->getGlobalSearchKeyBindings();
    $suffix = filament()->getGlobalSearchFieldSuffix();
@endphp

<div
    x-id="['input']" class="max-w-screen-sm" style="width: 100%"
>
    <label x-bind:for="$id('input')" class="sr-only">
        {{ __('filament-panels::global-search.field.label') }}
    </label>

    <x-filament::input.wrapper
        x-ref="searchInputWrapper"
        prefix-icon="heroicon-m-magnifying-glass"
        prefix-icon-alias="panels::global-search.field"
        inline-prefix
        :suffix="$suffix"
        inline-suffix
        wire:target="search"
    >
        <x-filament::input
            autocomplete="off"
            inline-prefix
            :placeholder="__('general.search_task_placeholder')"
            type="search"
            wire:key="global-search.field.input"
            x-bind:id="$id('input')"
            x-on:keydown.down.prevent.stop="$dispatch('focus-first-global-search-result')"
            x-data="{}"
            :attributes="
                \Filament\Support\prepare_inherited_attributes(
                    new \Illuminate\View\ComponentAttributeBag([
                        'wire:model.live.debounce.' . $debounce => 'search',
                        'x-mousetrap.global.' . collect($keyBindings)->map(fn (string $keyBinding): string => str_replace('+', '-', $keyBinding))->implode('.') => $keyBindings ? 'document.getElementById($id(\'input\')).focus()' : null,
                    ])
                )
            "
        />
    </x-filament::input.wrapper>

    @if ($results !== null)
        @include('components.searchbar.results-container', ['results' => $results])
    @endif
</div>
