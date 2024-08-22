<x-filament::section
    collapsible
    persist-collapsed
    style="margin-bottom: 30px; width: 100%"
    id="group-{{ $group->id }}">

    <x-slot name="heading">
        {{ $group->name }}
    </x-slot>

    @can('manageGroups', \App\Models\User::class)
        <x-slot name="headerEnd">
            <x-filament::icon-button
                icon="heroicon-o-pencil"
                wire:click.prevent="mountAction('editGroupAction', { 'group_id': '{{ $group->id }}' }); isCollapsed = ! isCollapsed"
                label="Edit label group"
                tooltip="{{ __('group.edit_group') }}"
            />
            <x-filament::icon-button
                color="danger"
                icon="heroicon-o-trash"
                wire:click.prevent="mountAction('deleteGroupAction', { 'group_id': '{{ $group->id }}' }); isCollapsed = ! isCollapsed"
                label="Delete group"
                tooltip="{{ __('group.delete') }}"
            />
        </x-slot>
    @endcan

    <ul wire:sortable-group.item-group="group-{{ $group->id }}" wire:sortable-group.options="{ animation: 100 }"
        class="py-1">
        @foreach($tasks->whereNull('parent_id') as $task)
            <livewire:task-row :$task :$sortBy :key="'task-' . $task->id . '-' . Illuminate\Support\Str::uuid()"/>
        @endforeach
    </ul>

    @can('manageTasks', \App\Models\User::class)
        <div class="mx-3 mt-2 pb-2 text-xs">
            {{ $this->createTaskAction }}
        </div>
    @endcan

    <x-filament-actions::modals/>
</x-filament::section>
