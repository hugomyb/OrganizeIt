<div class="flex items-start justify-center gap-2 text-sm font-semibold flex-wrap">
    <div class="flex items-center gap-2">
        @if($task->start_date && $task->due_date)
            <span class="text-gray-500 text-xs">{{ __('task.dates') }}</span>
            @can('manageDates', \App\Models\User::class)
                <div class="flex items-center gap-1 cursor-pointer" x-on:click="$wire.replaceMountedAction('updateDates', {task_id: {{ $task->id }}});">
                    <span class="font-bold">{{ $task->start_date->translatedFormat('d M') }}</span>
                    <x-heroicon-o-arrow-long-right class="h-5 w-5"/>
                    <span class="font-bold">{{ $task->due_date->translatedFormat('d M') }}</span>
                </div>
            @else
                <div class="flex items-center gap-1">
                    <span class="font-bold">{{ $task->start_date->translatedFormat('d M') }}</span>
                    <x-heroicon-o-arrow-long-right class="h-5 w-5"/>
                    <span class="font-bold">{{ $task->due_date->translatedFormat('d M') }}</span>
                </div>
            @endcan
        @elseif($task->start_date)
            <span class="text-gray-500 text-xs">{{ __('task.start_date') }}</span>
            @can('manageDates', \App\Models\User::class)
                <span class="font-bold cursor-pointer" x-on:click="$wire.replaceMountedAction('updateDates', {task_id: {{ $task->id }}});">{{ $task->start_date->translatedFormat('d M') }}</span>
            @else
                <span class="font-bold">{{ $task->start_date->translatedFormat('d M') }}</span>
            @endcan
        @elseif($task->due_date)
            <span class="text-gray-500 text-xs">{{ __('task.end_date') }}</span>
            @can('manageDates', \App\Models\User::class)
                <span class="font-bold cursor-pointer" x-on:click="$wire.replaceMountedAction('updateDates', {task_id: {{ $task->id }}});">{{ $task->due_date->translatedFormat('d M') }}</span>
            @else
                <span class="font-bold">{{ $task->due_date->translatedFormat('d M') }}</span>
            @endcan
        @else
            <span class="text-gray-500 text-xs">{{ __('task.dates') }}</span>
            @can('manageDates', \App\Models\User::class)
                <span class="font-bold cursor-pointer" x-on:click="$wire.replaceMountedAction('updateDates', {task_id: {{ $task->id }}});">{{ __('task.no_date') }}</span>
            @else
                <span class="font-bold">{{ __('task.no_date') }}</span>
            @endcan
        @endif
    </div>
</div>
