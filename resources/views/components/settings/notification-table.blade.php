@php($userSettings = $getState()['notifications'])

<x-filament-tables::table>
    @foreach($notificationTypes as $notificationClass => $notificationType)
        <x-filament-tables::row>
            <x-filament-tables::cell class="z-10 sticky left-0 bg-white dark:bg-gray-900 py-3">
                <div class="whitespace-normal grid gap-0.5">
                    <h4 class="text-sm font-medium text-gray-950 dark:text-white ps-2">
                        {{ $notificationType['label'] }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 ps-2">
                        {{ $notificationType['description'] }}
                    </p>
                </div>
            </x-filament-tables::cell>

            <x-filament-tables::cell class="!p-0 py-3">
                <div
                    x-data="{ enabled: false }"
                    x-init="enabled = @js($userSettings[class_basename($notificationClass)]['enabled'] ?? false)"
                    x-on:click="enabled = !enabled; $wire.call('toggleNotificationSetting', '{{ class_basename($notificationClass) }}', enabled)"
                    class="group h-full px-4 py-3 flex items-center justify-center cursor-pointer transition-all group hover:bg-primary-50/50 dark:hover:bg-white/5"
                >
                    <x-heroicon-s-check-circle
                        x-show="enabled"
                        class="w-8 h-8 group-hover:scale-110 transition-transform text-green-500 group-hover:text-green-600 dark:text-green-400/80 dark:group-hover:text-green-400"
                        defer
                    />

                    <x-heroicon-s-x-circle
                        x-show="!enabled"
                        class="w-8 h-8 group-hover:scale-110 transition-transform text-red-500 group-hover:text-red-600 dark:text-red-400/80 dark:group-hover:text-red-400"
                        defer
                    />
                </div>
            </x-filament-tables::cell>
        </x-filament-tables::row>

        <!-- Statuts spécifiques pour ChangeTaskStatusMail -->
        @if (class_basename($notificationClass) === 'ChangeTaskStatusMail')
            @foreach($statuses as $statusId => $statusName)
                <x-filament-tables::row>
                    <x-filament-tables::cell class="z-10 sticky left-0 bg-white dark:bg-gray-900 py-3">
                        <div class="whitespace-normal grid gap-0.5">
                            <h4 class="text-sm font-medium text-gray-950 dark:text-white" style="padding-left: 30px">
                                {{ $statusName }}
                            </h4>
                        </div>
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="!p-0 py-3">
                        <div
                            x-data="{ statusEnabled: false }"
                            x-init="statusEnabled = @js(in_array($statusId, $userSettings['ChangeTaskStatusMail']['statuses'] ?? []))"
                            x-on:click="statusEnabled = !statusEnabled; $wire.call('toggleStatusNotification', '{{ class_basename(\App\Mail\ChangeTaskStatusMail::class) }}', {{ $statusId }}, statusEnabled)"
                            class="group h-full px-4 py-3 flex items-center justify-center cursor-pointer transition-all group hover:bg-primary-50/50 dark:hover:bg-white/5"
                        >
                            <x-heroicon-s-check-circle
                                x-show="statusEnabled"
                                class="w-8 h-8 group-hover:scale-110 transition-transform text-green-500 group-hover:text-green-600 dark:text-green-400/80 dark:group-hover:text-green-400"
                                defer
                            />
                            <x-heroicon-s-x-circle
                                x-show="!statusEnabled"
                                class="w-8 h-8 group-hover:scale-110 transition-transform text-red-500 group-hover:text-red-600 dark:text-red-400/80 dark:group-hover:text-red-400"
                                defer
                            />
                        </div>
                    </x-filament-tables::cell>
                </x-filament-tables::row>
            @endforeach
        @endif
    @endforeach

    <svg hidden class="hidden">
        @stack('bladeicons')
    </svg>
</x-filament-tables::table>
