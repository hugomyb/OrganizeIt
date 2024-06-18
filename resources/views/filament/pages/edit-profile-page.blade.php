<x-filament-panels::page>

    <x-filament-panels::form wire:submit="updateProfile">
        {{ $this->profileForm }}

        <div class="fi-form-actions">
            <div class="flex flex-row-reverse flex-wrap items-center gap-3 fi-ac">
                <x-filament::button type="submit">
                    {{ __('general.save') }}
                </x-filament::button>
            </div>
        </div>
    </x-filament-panels::form>

    <x-filament-panels::form wire:submit="updatePassword">
        {{ $this->passwordForm }}

        <div class="fi-form-actions">
            <div class="flex flex-row-reverse flex-wrap items-center gap-3 fi-ac">
                <x-filament::button type="submit">
                    {{ __('general.save') }}
                </x-filament::button>
            </div>
        </div>
    </x-filament-panels::form>

</x-filament-panels::page>
