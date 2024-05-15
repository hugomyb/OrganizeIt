<x-filament-panels::page>
    <div class="flex justify-center items-center flex-col">
        @foreach($record->groups()->with('tasks.children', 'tasks.parent')->get() as $group)
            <x-filament::section collapsible style="margin-bottom: 30px; width: 100%" wire:key="group-{{ $group->id }}">
                <x-slot name="heading">
                    {{ $group->name }}
                </x-slot>

                <ul class="uk-nestable" data-uk-nestable=""
                    x-data="{
                    initNestable() {
                        const nestable = UIkit.nestable($el);
                        nestable.on('change.uk.nestable', (e, sortable, draggedElement, action) => {
                            const serialized = nestable.serialize();
                            $wire.updateTaskOrder('{{ $group->id }}', JSON.stringify(serialized));
                        });
                    }
                }" x-init="initNestable()">
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

        .uk-nestable-item {
            margin-top: 0 !important;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
            integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</x-filament-panels::page>
