<li x-sort:item="{{ $task->id }}"
    class="flex flex-col justify-between py-3 dark:hover:bg-white/5 text-sm {{ $task->children->isEmpty() ? 'border-b dark:border-white/10' : '' }}"
    style="padding-left: {{ $task->depth > 0 ? $task->depth * 28 : 5 }}px;">

    <div class="flex space-x-6">
        <div class="flex">
            <x-iconpark-drag x-sort:handle class="h-5 w-5 mx-1 text-gray-400"/>

            <x-filament::dropdown>
                <x-slot name="trigger">
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
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach(\App\Models\Status::all() as $status)
                        <x-filament::dropdown.list.item
                            wire:click="setTaskStatus({{$task->id}}, {{$status->id}})"
                            x-on:click="toggle"
                            class="text-xs font-bold">
                            <div class="flex items-center">
                                @switch($status->name)
                                    @case('À faire')
                                        <x-far-circle class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
                                        @break
                                    @case('En cours')
                                        <x-carbon-in-progress class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
                                        @break
                                    @case('Terminé')
                                        <x-grommet-status-good class="h-5 w-5 mx-1"
                                                               style="color: {{ $status->color }}"/>
                                        @break
                                    @default
                                        <x-far-circle class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
                                        @break
                                @endswitch
                                <span class="mx-1">{{ $status->name }}</span>
                            </div>
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>

            </x-filament::dropdown>
        </div>

        <div class="flex space-x-2 flex-wrap">
            <h3 class="font-medium mx-1 cursor-pointer hover:text-primary"
                wire:click="mountAction('viewTaskAction', { 'task_id': '{{$task->id}}' })">{{ $task->title . ' (' . $task->order . ')' }}</h3>

            @if($task->priority->name != 'Aucune')
                <x-filament::dropdown>
                    <x-slot name="trigger" class="flex items-center mx-1">
                        <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                        <span class="text-xs font-bold"
                              style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach(\App\Models\Priority::all() as $priority)
                            <x-filament::dropdown.list.item
                                wire:click="setTaskPriority({{$task->id}}, {{$priority->id}})"
                                x-on:click="toggle"
                                class="text-xs font-bold">
                                <div class="flex items-center">
                                    <x-iconsax-bol-flag-2 class="h-5 w-5 mx-1"
                                                          style="color: {{ $priority->color }}"/>
                                    <span class="mx-1">{{ $priority->name }}</span>
                                </div>
                            </x-filament::dropdown.list.item>
                        @endforeach
                    </x-filament::dropdown.list>

                </x-filament::dropdown>
            @endif
        </div>
    </div>
</li>

@if ($task->children->isNotEmpty())
    <ul class="list-none mx-1"
        x-data="{
            saveOrder: (item, position, toGroupId) => {
                 $wire.saveTaskOrder(item, position, toGroupId);
            }
        }"
        x-sort="saveOrder($item, $position, {{ $task->group_id }})"
        x-sort:group="tasks">
        @each('tasks.task', $task->children, 'task')
    </ul>
@endif
