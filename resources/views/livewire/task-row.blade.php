<li data-id="{{ $task->id }}"
    wire:sortable-group.item="{{ $task->id }}"
    class="flex flex-col justify-between dark:hover:bg-white/5 text-sm
    {{ (!$task->parent_id) ? 'border-b' : '' }}"
    style="padding-left: 8px;"
    x-init="init"
    x-data="{
        taskId: '{{ request()->has('task') ? request()->get('task') : null }}',

        init() {
            if(this.taskId && this.taskId == '{{ $task->id }}') {
                $wire.openTaskById();
            }

            Livewire.on('close-modal', () => {
                this.taskId = null;

                window.history.pushState({}, document.title, window.location.pathname);
                $wire.dispatch('modal-closed');
            });
        }
    }"
>
    <div class="flex py-3 content-item"
         x-data="{ isOver: false }"
         x-on:drop.prevent="
            const userId = event.dataTransfer.getData('user-id');
            if (userId) {
                $wire.assignUserToTask(userId, '{{ $task->id }}');
            }
            const priorityId = event.dataTransfer.getData('priority-id');
            if (priorityId) {
                $wire.setTaskPriority(priorityId);
            }
            isOver = false;
        "
         x-on:dragover.prevent="isOver = true"
         x-on:dragleave="isOver = false"
         :class="{ 'highlight': isOver }">
        <div class="flex">
            @can('reorderTasks', \App\Models\User::class)
                @if($sortBy === 'default')
                    <x-iconpark-drag
                        wire:sortable-group.handle
                        class="h-5 w-5 mx-1 text-gray-400 cursor-move"/>
                @endif
            @endcan

            @can('changeStatus', \App\Models\User::class)
                <!-- Status dropdown -->
                <x-filament::dropdown>
                    <x-slot name="trigger"
                            style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4' : '' }}">
                        @switch($task->status->name)
                            @case('À faire')
                                <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                            style="color: {{ $task->status->color }};"/>
                                @break
                            @case('En cours')
                                <x-carbon-in-progress class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                                @break
                            @case('Terminé')
                                <x-grommet-status-good class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                                @break
                            @default
                                <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                            style="color: {{ $task->status->color }}"/>
                        @endswitch
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach(\App\Models\Status::all() as $status)
                            <x-filament::dropdown.list.item
                                wire:click="setTaskStatus({{$status->id}})"
                                x-on:click="toggle"
                                class="text-xs font-bold">
                                <div class="flex items-center">
                                    @switch($status->name)
                                        @case('À faire')
                                            <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                                        style="color: {{ $status->color }}"/>
                                            @break
                                        @case('En cours')
                                            <x-carbon-in-progress class="h-5 w-5 mx-1"
                                                                  style="color: {{ $status->color }}"/>
                                            @break
                                        @case('Terminé')
                                            <x-grommet-status-good class="h-5 w-5 mx-1"
                                                                   style="color: {{ $status->color }}"/>
                                            @break
                                        @default
                                            <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                                        style="color: {{ $status->color }}"/>
                                            @break
                                    @endswitch
                                    <span class="mx-1"
                                          title="{{ $status->name }}">{{ \Illuminate\Support\Str::limit($status->name, 25) }}</span>
                                </div>
                            </x-filament::dropdown.list.item>
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @else
                @switch($task->status->name)
                    @case('À faire')
                        <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                    style="color: {{ $task->status->color }};"/>
                        @break
                    @case('En cours')
                        <x-carbon-in-progress class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                        @break
                    @case('Terminé')
                        <x-grommet-status-good class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                        @break
                    @default
                        <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                    style="color: {{ $task->status->color }}"/>
                @endswitch
            @endcan
        </div>

        <div class="flex gap-2 items-center flex-wrap">
            <h3 class="font-medium mx-1 cursor-pointer hover:text-primary"
                style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4;' : '' }}"
                wire:click="mountAction('viewTaskAction', { 'task_id': '{{$task->id}}' })">{{ $task->title }}</h3>

            @if($task->children->count() > 0)
                <x-filament::badge color="gray" class="cursor-default"
                                   style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4;' : '' }}">
                    <span class="text-gray-400">{{ $task->children->where('status_id', \App\Models\Status::getCompletedStatusId())->count() }}/{{ $task->children->count() }}</span>
                </x-filament::badge>
            @endif

            @can('changePriority', \App\Models\User::class)
                <!-- Priority dropdown -->
                @if($task->priority->name != \App\Models\Priority::where('name', 'Aucune')->first()->name)
                    <x-filament::dropdown>
                        <x-slot name="trigger" class="flex items-center mx-1"
                                style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4;' : '' }}">
                            <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                            <span class="text-xs font-bold task-title"
                                  style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
                        </x-slot>

                        <x-filament::dropdown.list>
                            @foreach(\App\Models\Priority::all() as $priority)
                                <x-filament::dropdown.list.item
                                    wire:click="setTaskPriority({{$priority->id}})"
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
            @else
                @if($task->priority->name != \App\Models\Priority::where('name', 'Aucune')->first()->name)
                    <div class="flex items-center mx-1">
                        <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                        <span class="text-xs font-bold task-title"
                              style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
                    </div>
                @endif
            @endcan

            @can('viewAssignedUsers', \App\Models\User::class)
                @if($task->users()->count() > 0)
                    @can('assignUser', \App\Models\User::class)
                        <x-filament::dropdown>
                            <x-slot name="trigger" class="flex gap-1 items-center"
                                    style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4;' : '' }}">
                                @foreach($task->users as $user)
                                    <div class="flex gap-1 items-center cursor-pointer"
                                         wire:key="avatar-{{ $user->id }}">
                                        <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                             class="w-5 h-5 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
                                        @if($task->users()->count() == 1)
                                            <span class="text-xs"
                                                  style="color: gray; font-weight: 600">{{ $user->name }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </x-slot>

                            <x-filament::dropdown.list>
                                @foreach($task->project->users as $user)
                                    <x-filament::dropdown.list.item
                                        wire:click="toggleUserToTask({{$user->id}}, {{$task->id}})">
                                        <div class="text-xs font-bold flex justify-between items-center">
                                            <div class="flex items center gap-1 items-center">
                                                <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                                     class="w-5 h-5 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
                                                <span class="mx-1">{{ $user->name }}</span>
                                            </div>
                                            @if($task->users->contains($user))
                                                {{ svg('gmdi-check-box-r', attributes: ['style' => 'fill: #22c55e; width: 1.5rem; height: 1.5rem;']) }}
                                            @else
                                                {{ svg('gmdi-check-box-outline-blank-o', 'h-6 w-6', ['style' => 'fill: gray']) }}
                                            @endif
                                        </div>
                                    </x-filament::dropdown.list.item>
                                @endforeach
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    @else
                        @foreach($task->users as $user)
                            <div class="flex gap-1 items-center">
                                <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                     class="w-5 h-5 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
                                @if($task->users()->count() == 1)
                                    <span class="text-xs" style="color: gray; font-weight: 600">{{ $user->name }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endcan
                @endif
            @endcan

            @if($task->description)
                <x-gmdi-description-o
                    wire:click="mountAction('viewTaskAction', { 'task_id': '{{$task->id}}' })"
                    icon="gmdi-description-o"
                    class="h-5 w-5 cursor-pointer tooltip-link relative text-gray-400 hover:text-gray-700/75"/>
            @endif

            @if(count($task->attachments) > 0)
                <div class="text-gray-400 hover:text-gray-700/75 flex items-center gap-1">
                    <x-heroicon-o-folder
                        x-on:click="$wire.mountAction('viewTaskAction', { 'task_id': '{{$task->id}}' });"
                        class="h-5 w-5 cursor-pointer tooltip-link relative"/>
                    <span class="text-xs">{{ count($task->attachments) }}</span>
                </div>
            @endif

            @if($task->comments->count() > 0)
                <div class="text-gray-400 hover:text-gray-700/75 flex items-center gap-1">
                    <x-forkawesome-comments-o
                        x-on:click="$wire.mountAction('viewTaskAction', { 'task_id': '{{$task->id}}' });"
                        class="h-5 w-5 cursor-pointer tooltip-link relative"/>
                    <span class="text-xs">{{ $task->comments->count() }}</span>
                </div>
            @endif
        </div>

        <!-- actions task -->
        @can('manageTasks', \App\Models\User::class)
            <div class="task-buttons absolute hidden group-hover:flex">
                {{ $this->editTaskTooltipAction }}
                {{ $this->addSubtaskTooltipAction }}
                {{ $this->deleteTaskTooltipAction }}
            </div>
        @endcan
    </div>

    <ul wire:sortable-group.item-group="{{ $task->id }}"
        wire:sortable-group.options="{ animation: 100 }"
        style="margin-left: 20px"
        class="child-list">
        @foreach ($sortedChildren as $childTask)
            <livewire:task-row :task="$childTask" :sortBy="$sortBy"
                               :key="'task-' . $childTask->id . '-child'"/>
        @endforeach
    </ul>

    <x-filament-actions::modals/>
</li>
