<div>
    <x-filament-panels::page.simple>

        <x-filament-panels::form wire:submit="apply">
            {{ $this->form }}

            <x-filament::button type="submit">
                {{ __('general.save') }}
            </x-filament::button>
        </x-filament-panels::form>

    </x-filament-panels::page.simple>
</div>
