<x-filament-panels::page>
    <div class="flex justify-center items-center flex-col">
        @foreach($record->groups()->with('tasks.children', 'tasks.parent')->get() as $group)
            <x-filament::section
                collapsible
                persist-collapsed
                style="margin-bottom: 30px; width: 100%"
                wire:key="group-{{ $group->id }}"
                id="group-{{ $group->id }}">

                <x-slot name="heading">
                    {{ $group->name }}
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::icon-button
                        icon="heroicon-o-pencil"
                        wire:click="mountAction('editGroupAction', { 'group_id': '{{ $group->id }}' })"
                        label="Edit label group"
                        tooltip="Éditer groupe"
                    />
                    <x-filament::icon-button
                        color="danger"
                        icon="heroicon-o-trash"
                        wire:click="mountAction('deleteGroupAction', { 'group_id': '{{ $group->id }}' })"
                        label="Delete group"
                        tooltip="Supprimer"
                    />
                </x-slot>

                <ul class="uk-nestable" data-uk-nestable="{group:'task-groups', handleClass:'uk-nestable-handle'}"
                    x-data="{
                        initNestable() {
                            const nestable = UIkit.nestable($el);
                            nestable.on('change.uk.nestable', (e, sortable, draggedElement, action) => {
                                const serialized = nestable.serialize().filter(item => item.id !== 'placeholder');
                                $wire.updateTaskOrder('{{ $group->id }}', JSON.stringify(serialized));
                            });
                        }
                    }" x-init="initNestable()">
                    @forelse($group->tasks()->whereNull('parent_id')->get()->sortBy('order') as $task)
                        @include('tasks.task', ['task' => $task])
                    @empty
                        <li class="uk-nestable-item placeholder dark:hover:bg-white/5" data-id="placeholder">
                            <div class="uk-nestable-content bg-transparent" style="height: 1px"></div>
                        </li>
                    @endforelse
                </ul>

                <div class="mx-3 mt-3 mb-2 text-xs">
                    {{ ($this->createTaskAction)(['group_id' => $group->id]) }}
                </div>
            </x-filament::section>
        @endforeach

        {{ $this->createGroupAction }}

    </div>

    <x-filament-actions::modals/>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
            integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</x-filament-panels::page>
