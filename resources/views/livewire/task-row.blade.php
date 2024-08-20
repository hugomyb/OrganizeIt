<li data-id="{{ $task->id }}"
    wire:sortable-group.item="{{ $task->id }}"
    wire:key="task-{{ $task->id }}"
    class="flex flex-col justify-between dark:hover:bg-white/5 text-sm
    {{ (!$task->parent_id) ? 'border-b' : '' }}"
    style="padding-left: 8px;">

    <div class="flex items-center space-x-1">
        <x-iconpark-drag
            wire:sortable-group.handle
            class="h-5 w-5 mx-1 text-gray-400 cursor-move"/>
        <p>{{ $task->title }}</p>
    </div>

    <!-- Section pour les sous-tâches -->
    <ul wire:sortable-group.item-group="{{ $task->id }}" wire:sortable-group.options="{ animation: 100 }" class="py-1 child-list" style="margin-left: 10px">
        @foreach($task->children as $childTask)
            <livewire:task-row :task="$childTask" :key="$childTask->id" />
        @endforeach
    </ul>
</li>
