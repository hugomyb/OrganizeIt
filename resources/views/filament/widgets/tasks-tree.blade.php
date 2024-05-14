<x-filament-widgets::widget>
    <div class="flex justify-center items-center flex-col">
        @foreach($record->groups as $group)
            <x-filament::section collapsible style="margin-bottom: 30px; width: 100%" wire:key="group-{{ $group->id }}">
                <x-slot name="heading">
                    {{ $group->name }}
                </x-slot>

                <ul class="list-none">
                    @each('tasks.task', $group->tasks, 'task')
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
</x-filament-widgets::widget>
