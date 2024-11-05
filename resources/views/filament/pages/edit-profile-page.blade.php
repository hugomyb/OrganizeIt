<x-filament-panels::page
    x-init="
        if (window.location.hash === '#settings') {
            document.getElementById('settings-section').scrollIntoView({ behavior: 'smooth' });
        }
    ">

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

    @if(!auth()->user()->hasRole('Client'))
        <x-filament-panels::form wire:submit="updateSettings" id="settings-section">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('profile.settings') }}
                </h3>
                <p class="fi-section-header-description overflow-hidden break-words text-sm text-gray-500 dark:text-gray-400">
                    {{ __('profile.update_settings') }}
                </p>
            </div>

            {{ $this->settingsForm }}
        </x-filament-panels::form>
    @endif
</x-filament-panels::page>
