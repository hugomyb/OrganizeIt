<li class="flex items-center justify-between px-3 py-3 dark:hover:bg-white/5 rounded border-b dark:border-white/10 text-sm"
    style="margin-left: {{ $task->depth * 20 }}px;">

    <div class="flex items-center space-x-6 w-full">
        <x-iconpark-drag class="h-5 w-5 mx-1 text-gray-400"/>

        @switch($task->status->name)
            @case('À faire')
                <x-far-circle class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                @break
            @case('En cours')
                <x-carbon-in-progress class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                @break
            @case('Terminé')
                <x-grommet-status-good class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                @break
            @default
                <x-far-circle class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
        @endswitch

        <h3 class="font-medium mx-1 cursor-pointer hover:text-primary" wire:click="mountAction('editTaskAction', { 'task_id': '{{$task->id}}' })">{{ $task->title }}</h3>
    </div>

    @if ($task->children->isNotEmpty())
        <ul class="list-none">
            @each('tasks.partials.task', $task->children, 'task')
        </ul>
    @endif
</li>
