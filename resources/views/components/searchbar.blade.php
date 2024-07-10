<x-filament::input.wrapper
    prefix-icon="heroicon-m-magnifying-glass"
    prefix-icon-alias="panels::global-search.field"
    inline-prefix
    inline-suffix
    wire:target="search"
    :style="'min-width: 30%'"
>
    <x-filament::input
        autocomplete="off"
        inline-prefix
        :placeholder="__('general.search_task_placeholder')"
        type="search"
        wire:key="search.field.input"
        x-data="{}"
        :attributes="
                \Filament\Support\prepare_inherited_attributes(
                    new \Illuminate\View\ComponentAttributeBag([
                        'wire:model.live' => 'search',
                    ])
                )
            "
    />
</x-filament::input.wrapper>
