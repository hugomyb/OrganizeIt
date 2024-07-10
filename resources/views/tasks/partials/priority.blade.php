<div class="flex items-center justify-center gap-2 text-sm font-semibold">
    <span class="text-gray-500 text-xs">{{ __('priority.priority') }}</span>
    <div class="flex items-center gap-1">
        @can('changePriority', \App\Models\User::class)
            <x-filament::dropdown>
                <x-slot name="trigger" class="flex items-center">
                    <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                    <span class="text-xs font-bold task-title" style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach(\App\Models\Priority::all() as $priority)
                        <x-filament::dropdown.list.item wire:click="setTaskPriority({{$task->id}}, {{$priority->id}})" x-on:click="toggle" class="text-xs font-bold">
                            <div class="flex items-center">
                                <x-iconsax-bol-flag-2 class="h-5 w-5 mx-1" style="color: {{ $priority->color }}"/>
                                <span class="mx-1">{{ $priority->name }}</span>
                            </div>
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @else
            <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
            <span class="text-xs font-bold task-title" style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
        @endcan
    </div>
</div>
