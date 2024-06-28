<x-filament-panels::page
    x-init="init"
    x-data="{
        taskId: '{{ request()->has('task') ? request()->get('task') : null }}',

        init() {
            if(this.taskId) {
                $wire.openTaskById(this.taskId);
            }

            Livewire.on('close-modal', () => {
                this.taskId = null;

                window.history.pushState({}, document.title, window.location.pathname);
                $wire.dispatch('modal-closed');
            });
        }
    }"
>
    <div class="flex justify-center items-start w-full gap-6">
        <div style="width: 78%" class="flex justify-center items-center flex-col">
            @foreach($groups as $group)
                <x-filament::section
                    collapsible
                    style="margin-bottom: 30px; width: 100%"
                    wire:key="group-{{ $group->id }}"
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

                    <ul class="uk-nestable"
                        data-uk-nestable="{group:'task-groups', handleClass:'uk-nestable-handle'}"
                        x-data="{
                            initNestable() {
                                const nestable = UIkit.nestable($el);
                                nestable.on('change.uk.nestable', (e, sortable, draggedElement, action) => {
                                    const serialized = nestable.serialize().filter(item => item.id !== 'placeholder');
                                    $wire.updateTaskOrder('{{ $group->id }}', JSON.stringify(serialized));
                                });
                            }
                        }"
                        x-init="initNestable()">
                        @forelse($group->tasks->whereNull('parent_id') as $task)
                            @include('tasks.task', ['task' => $task, 'sortBy' => $sortBy, 'project' => $record])
                        @empty
                            <li class="uk-nestable-item placeholder dark:hover:bg-white/5" data-id="placeholder">
                                <div class="uk-nestable-content bg-transparent" style="height: 1px"></div>
                            </li>
                        @endforelse
                    </ul>

                    @can('manageTasks', \App\Models\User::class)
                        <div class="mx-3 mt-3 mb-2 text-xs">
                            {{ ($this->createTaskAction)(['group_id' => $group->id]) }}
                        </div>
                    @endcan
                </x-filament::section>
            @endforeach

            @can('manageGroups', \App\Models\User::class)
                {{ $this->createGroupAction }}
            @endcan
        </div>

        <div style="width: 20%; top: 85px"
             class="flex flex-col gap-4 items-center justify-center sticky bg-white dark:bg-gray-900 dark:ring-white/10 p-4 ring-1 ring-gray-950/5 shadow-sm rounded-xl"
             x-data>
            @canany(['changePriority', 'assignUser'], \App\Models\User::class)
                <x-filament::section
                    collapsible
                    style="width: 100%">
                    <x-slot name="heading">
                        {{ __('general.drag_drop') }}
                    </x-slot>

                    <div class="flex flex-col px-6 py-3 gap-3">
                        @can('assignUser', \App\Models\User::class)
                            <div style="margin-bottom: 10px">
                                <div class="flex items-center justify-between gap-1" style="margin-bottom: 10px">
                                    <div class="flex items-center gap-1">
                                        <x-filament::icon icon="heroicon-s-users" class="w-5 h-5"
                                                          style="color: gray"/>
                                        <span style="font-weight: 500;">{{ __('general.assign') }}</span>
                                    </div>

                                    @can('addUserToProject', \App\Models\User::class)
                                        <x-filament::dropdown>
                                            <x-slot name="trigger">
                                                <x-heroicon-s-user-plus class="h-4 w-4 cursor-pointer filter-icon"/>
                                            </x-slot>

                                            <x-filament::dropdown.list>
                                                @forelse(\App\Models\User::whereNotIn('id', $record->users->pluck('id'))->get() as $user)
                                                    <x-filament::dropdown.list.item
                                                        wire:click="addUserToProject({{$user->id}})"
                                                        x-on:click="toggle">
                                                        <div
                                                            class="text-xs font-bold flex justify-between items-center"
                                                            title="Assigner au projet">
                                                            <div class="flex items center gap-1 items-center">
                                                                <img src="/storage/{{ $user->avatar_url }}"
                                                                     alt="{{ $user->name }}"
                                                                     class="w-5 h-5 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
                                                                <span class="mx-1">{{ $user->name }}</span>
                                                            </div>

                                                            <x-heroicon-s-plus
                                                                class="h-4 w-4 cursor-pointer filter-icon"/>
                                                        </div>
                                                    </x-filament::dropdown.list.item>
                                                @empty
                                                    <div class="flex justify-center py-1">
                                                            <span
                                                                class="dark:text-white text-xs text-center text-gray-600">{{ __('general.no_user_to_add') }}</span>
                                                    </div>
                                                @endforelse
                                            </x-filament::dropdown.list>
                                        </x-filament::dropdown>
                                    @endcan
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($record->users as $user)
                                        <div
                                            style="font-weight: 500;"
                                            class="bg-gray-100 dark:bg-gray-800 dark:hover:bg-white/5 px-1.5 py-1 rounded-lg flex items-center text-xs gap-1 cursor-move"
                                            draggable="true"
                                            x-on:dragstart="event.dataTransfer.setData('user-id', '{{ $user->id }}')">
                                            <img class="rounded-full h-5" src="/storage/{{ $user->avatar_url }}" alt="">
                                            <span class="dark:text-white text-gray-600">{{ $user->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endcan

                        @can('changePriority', \App\Models\User::class)
                            <div style="margin-bottom: 10px">
                                <div class="flex items-center gap-1" style="margin-bottom: 10px">
                                    <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: gray"/>
                                    <span style="font-weight: 500;">{{ __('priority.priority') }}</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(\App\Models\Priority::all() as $priority)
                                        <div
                                            style="font-weight: 500; background-color: {{ $priority->color }}; color: white;"
                                            class="dark:hover:bg-white/5 px-1.5 py-1 rounded-lg flex items-center text-xs gap-1 cursor-move"
                                            draggable="true"
                                            x-on:dragstart="event.dataTransfer.setData('priority-id', '{{ $priority->id }}')">
                                            <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: white"/>
                                            <span class="text-white">{{ $priority->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endcan
                    </div>

                </x-filament::section>
            @endcanany

            <!-- filters -->
            <x-filament::section
                collapsible
                style="width: 100%">
                <x-slot name="heading">
                    {{ __('general.filters') }}
                </x-slot>

                <div class="flex flex-col px-6 py-3 gap-3">
                    <div style="margin-bottom: 10px">
                        <div class="flex items-center justify-between gap-1" style="margin-bottom: 10px">
                            <span style="font-weight: 500;">{{ __('status.status') }}</span>

                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-heroicon-s-funnel class="h-4 w-4 cursor-pointer filter-icon"/>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    @foreach(\App\Models\Status::all() as $status)
                                        <x-filament::dropdown.list.item
                                            wire:click="updateStatusFilter({{$status->id}})"
                                            class="text-xs font-bold">
                                            <div class="flex items-center justify-between">
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
                                                    <span class="mx-1">{{ \Illuminate\Support\Str::limit($status->name, 20) }}</span>
                                                </div>

                                                @if(in_array($status->id, collect($statusFilters)->pluck('id')->toArray()))
                                                    {{ svg('gmdi-check-box-r', attributes: ['style' => 'fill: #22c55e; width: 1.5rem; height: 1.5rem;']) }}
                                                @else
                                                    {{ svg('gmdi-check-box-outline-blank-o', 'h-6 w-6', ['style' => 'fill: gray']) }}
                                                @endif
                                            </div>
                                        </x-filament::dropdown.list.item>
                                    @endforeach
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                        <div class="flex flex-col gap-2">
                            @forelse($statusFilters as $status)
                                <div
                                    style="font-weight: 500;"
                                    class="w-full bg-gray-100 dark:bg-gray-800 dark:hover:bg-white/5 px-3 py-2 rounded-lg flex items-center text-xs justify-between">
                                    <div class="flex items-center gap-1">
                                        @switch($status['name'])
                                            @case('À faire')
                                                <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                                            style="color: {{ $status['color'] }}"/>
                                                @break
                                            @case('En cours')
                                                <x-carbon-in-progress class="h-5 w-5 mx-1"
                                                                      style="color: {{ $status['color'] }}"/>
                                                @break
                                            @case('Terminé')
                                                <x-grommet-status-good class="h-5 w-5 mx-1"
                                                                       style="color: {{ $status['color'] }}"/>
                                                @break
                                            @default
                                                <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                                            style="color: {{ $status['color'] }}"/>
                                        @endswitch
                                        <span class="dark:text-white text-gray-600">{{ $status['name'] }}</span>
                                    </div>

                                    <x-heroicon-o-x-circle wire:click="updateStatusFilter({{$status['id']}})"
                                                           class="h-5 w-5 cursor-pointer" style="color: gray"/>
                                </div>
                            @empty
                                <span
                                    class="dark:text-white text-xs text-center text-gray-600">{{ __('general.no_filter') }}</span>
                            @endforelse
                        </div>
                    </div>

                    <div style="margin-bottom: 10px">
                        <div class="flex items-center justify-between gap-1" style="margin-bottom: 10px">
                            <span style="font-weight: 500;">{{ __('priority.priority') }}</span>

                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-heroicon-s-funnel class="h-4 w-4 cursor-pointer filter-icon"/>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    @foreach(\App\Models\Priority::all() as $priority)
                                        <x-filament::dropdown.list.item
                                            wire:click="updatePriorityFilter({{$priority->id}})"
                                            class="text-xs font-bold">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <x-iconsax-bol-flag-2 class="h-5 w-5 mx-1"
                                                                          style="color: {{ $priority->color }}"/>
                                                    <span class="mx-1">{{ $priority->name }}</span>
                                                </div>

                                                @if(in_array($priority->id, collect($priorityFilters)->pluck('id')->toArray()))
                                                    {{ svg('gmdi-check-box-r', attributes: ['style' => 'fill: #22c55e; width: 1.5rem; height: 1.5rem;']) }}
                                                @else
                                                    {{ svg('gmdi-check-box-outline-blank-o', 'h-6 w-6', ['style' => 'fill: gray']) }}
                                                @endif
                                            </div>
                                        </x-filament::dropdown.list.item>
                                    @endforeach
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                        <div class="flex flex-col gap-2">
                            @forelse($priorityFilters as $priority)
                                <div
                                    style="font-weight: 500;"
                                    class="w-full bg-gray-100 dark:bg-gray-800 dark:hover:bg-white/5 px-3 py-2 rounded-lg flex items-center justify-between text-xs gap-1 cursor-pointer">
                                    <div class="flex items-center gap-1">
                                        <x-iconsax-bol-flag-2 class="h-5 w-5"
                                                              style="color: {{ $priority['color'] }}"/>
                                        <span class="dark:text-white text-gray-600">{{ $priority['name'] }}</span>
                                    </div>

                                    <x-heroicon-o-x-circle wire:click="updatePriorityFilter({{$priority['id']}})"
                                                           class="h-5 w-5 cursor-pointer" style="color: gray"/>
                                </div>
                            @empty
                                <span
                                    class="dark:text-white text-xs text-center text-gray-600">{{ __('general.no_filter') }}</span>
                            @endforelse
                        </div>
                    </div>

                    <div
                        wire:click="toggleShowCompletedTasks()"
                        style="{{ $toggleCompletedTasks ? '' : 'background-color: #2563eb; color: #fff' }}"
                        class="border border-gray-200 dark:border-white/10 text-center rounded-lg px-2 text-sm py-2 justify-center flex items-center gap-1 cursor-pointer">
                        {{ $toggleCompletedTasks ? __('general.hide_completed_task') : __('general.completed_task_hidden') }}
                    </div>
                </div>
            </x-filament::section>

            <!-- sort -->
            <x-filament::section
                collapsible
                style="width: 100%">
                <x-slot name="heading">
                    {{ __('general.sort_by') }}
                </x-slot>

                <div class="flex flex-col px-6 py-3 gap-3">
                    <div class="text-xs font-semibold">
                        <div style="font-weight: 500; {{ $sortBy === 'priority' ? 'background-color: #2563eb; color: #fff !important' : '' }}"
                             wire:click="toggleSortByPriority()"
                             class="w-full cursor-pointer border border-gray-100 dark:border-gray-700 px-3 py-2 rounded-lg flex items-center text-xs justify-between">
                            <div class="flex items-center">
                                <x-iconsax-bol-flag-2 class="h-5 w-5 mx-1" />
                                <span class="mx-1">{{ __('priority.priority') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

        </div>
    </div>

    <x-filament-actions::modals/>
</x-filament-panels::page>
