@props([
    'task'
])

<li
    {{ $attributes->class(['fi-global-search-result scroll-mt-9 transition duration-75 focus-within:bg-gray-50 hover:bg-gray-50 dark:focus-within:bg-white/5 dark:hover:bg-white/5']) }}
>
    <a
        x-on:click="close(); document.activeElement.blur();"
        wire:click="openTask('{{ $task['id'] }}')"
        @class([
            'fi-global-search-result-link block outline-none cursor-pointer p-4'
        ])
    >
        <div class="flex gap-2">
            @switch($task['status']['name'])
                @case('À faire')
                    <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                style="color: {{ $task['status']['color'] }}; min-width: 1.25rem"/>
                    @break
                @case('En cours')
                    <x-carbon-in-progress class="h-5 w-5 mx-1"
                                          style="color: {{ $task['status']['color'] }}; min-width: 1.25rem"/>
                    @break
                @case('Terminé')
                    <x-grommet-status-good class="h-5 w-5 mx-1"
                                           style="color: {{ $task['status']['color'] }}; min-width: 1.25rem"/>
                    @break
                @default
                    <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                style="color: {{ $task['status']['color'] }}; min-width: 1.25rem"/>
            @endswitch

            <div class="flex gap-2 items-center flex-wrap">

                <h4 class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ $task['title'] }}
                </h4>

                <div class="flex items-center mx-1">
                    <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task['priority']['color'] }}"/>
                    <span class="text-xs font-bold task-title"
                          style="color: {{ $task['priority']['color'] }}">{{ $task['priority']['name'] }}</span>
                </div>

                @foreach($task['users'] as $user)
                    <div class="flex gap-1 items-center">
                        <img src="/storage/{{ $user['avatar_url'] }}" alt="{{ $user['name'] }}"
                             class="w-5 h-5 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
                        @if(count($task['users']) == 1)
                            <span class="text-xs" style="color: gray; font-weight: 600">{{ $user['name'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </a>
</li>
