<x-filament-panels::page>
    <div class="flex justify-center items-center flex-col">
        @foreach($record->groups()->with('tasks.children')->get() as $group)
            <x-filament::section collapsible style="margin-bottom: 30px; width: 100%" wire:key="group-{{ $group->id }}">
                <x-slot name="heading">
                    {{ $group->name }}
                </x-slot>

                <ul class="list-none mx-1"
                    x-data="{
                        saveOrder: (item, position, toGroupId, parentId) => {
                            $wire.saveTaskOrder(item, position, toGroupId, parentId);
                        }
                    }"
                    x-sort="saveOrder($item, $position, {{ $group->id }}, null)"
                    x-sort:group="tasks">
                    @each('tasks.task', $group->tasks()->whereNull('parent_id')->get()->sortBy('order'), 'task')
                </ul>

                <div class="mx-3 mt-3 mb-2 text-xs">
                    {{ ($this->createTaskAction)(['group_id' => $group->id]) }}
                </div>
            </x-filament::section>
        @endforeach

        {{ $this->createGroupAction }}

        <x-filament-actions::modals/>
    </div>
    <style>
        .fi-section-content {
            padding: 0 !important;
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sort@3.x.x/dist/cdn.min.js"></script>
</x-filament-panels::page>
