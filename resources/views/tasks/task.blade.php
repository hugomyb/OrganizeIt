<li data-id="{{ $task->id }}"
    class="uk-nestable-item flex flex-col justify-between dark:hover:bg-white/5 text-sm {{ (!$task->parent_id && $task->children->isEmpty()) ? 'border-b dark:border-white/10' : '' }}"
    style="padding-left: 8px;">
    <div class="flex py-3 content-item"
         x-data
         x-on:drop.prevent="
            const userId = event.dataTransfer.getData('user-id');
            $wire.assignUserToTask(userId, '{{ $task->id }}')"
         x-on:dragover.prevent>
        <div class="flex">
            <x-iconpark-drag class="h-5 w-5 mx-1 text-gray-400 cursor-move uk-nestable-handle"/>

            <!-- Status dropdown -->
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

        <div class="flex gap-1 items-center flex-wrap">
            <h3 class="font-medium mx-1 cursor-pointer hover:text-primary"
                wire:click="mountAction('viewTaskAction', { 'task_id': '{{$task->id}}' })">{{ $task->title }}</h3>

            <!-- Priority dropdown -->
            @if($task->priority->name != 'Aucune')
                <x-filament::dropdown>
                    <x-slot name="trigger" class="flex items-center mx-1">
                        <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                        <span class="text-xs font-bold task-title"
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

            @if($task->users()->exists())
                <div class="flex gap-1 items-center">
                    @foreach($task->users as $user)
                        <div class="flex gap-1 items-center cursor-pointer">
                            <img src="/storage/{{ $user->avatar }}" alt="{{ $user->name }}"
                                 class="w-6 h-6 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
                            @if($task->users()->count() == 1)
                                <span class="text-xs" style="color: gray; font-weight: 600">{{ $user->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- actions task -->
        <div class="task-buttons absolute flex hidden group-hover:flex">
            {{ ($this->editTaskTooltipAction)(['task_id' => $task->id]) }}
            {{ ($this->addSubtaskTooltipAction)(['parent_id' => $task->id, 'group_id' => $task->group_id]) }}
            {{ ($this->deleteTaskTooltipAction)(['task_id' => $task->id]) }}
        </div>
    </div>

    @if ($task->children->isNotEmpty())
        <ul class="uk-nestable-list child-list">
            @each('tasks.task', $task->children, 'task')
        </ul>
    @endif
</li>
