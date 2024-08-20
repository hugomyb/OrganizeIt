<x-filament::section
    collapsible
    persist-collapsed
    style="margin-bottom: 30px; width: 100%"
    id="group-{{ $group->id }}">

    <x-slot name="heading">
        {{ $group->name }}
    </x-slot>

    <ul wire:sortable-group.item-group="group-{{ $group->id }}" wire:sortable-group.options="{ animation: 100 }" class="py-1">
        @foreach($tasks->whereNull('parent_id') as $task)
            <livewire:task-row :task="$task" :key="$task->id" />
        @endforeach
    </ul>

</x-filament::section>
