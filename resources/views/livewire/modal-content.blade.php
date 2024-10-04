<div class="flex flex-col">
    <div class="flex items-center justify-between">
        @include('infolists.components.breadcrumb-entry', ['record' => $task])
        <x-filament::button
            outlined
            icon="phosphor-share"
            color="primary"
            class="ml-auto"
            size="sm"
            x-on:click="navigator.clipboard.writeText(window.location.href); $wire.showNotification('{{__('general.link_copied')}}'); ">
            {{ __('general.share') }}
        </x-filament::button>
    </div>

    <div
        class="flex items-center gap-3 mt-6 border-b border-gray-100 dark:border-gray-700 pb-6 task-title">
        @if($task->creator && auth()->user()->hasPermission('view_task_creator'))
            <img src="/storage/{{ $task->creator->avatar_url }}" alt="{{ $task->creator->name }}"
                 title="{{ $task->creator->name }}"
                 class="rounded-full" style="height: 60px">
        @else
            <img class="rounded-full" src="{{ asset('img/avatar.png') }}" alt="avatar" style="height: 60px">
        @endif

        <span class="text-xl font-semibold relative inline-block"
              id="titleEditable"
              x-init="initContentEditable"
              x-data="{
                initContentEditable() {
                    document.getElementById('titleEditable').addEventListener('keypress', (evt) => {
                        if (evt.which === 13) {
                            evt.preventDefault();
                        }
                    });
                },
              }"
              x-ref="taskTitle"
              contenteditable="false"
              x-on:blur="$wire.saveTaskTitle($event.target.innerText)"
              @keyup.enter.prevent="$event.target.blur()">
            {{ $task->title }}

            @can('manageTasks', \App\Models\User::class)
                <x-phosphor-pencil class="h-5 w-5 absolute inline edit-title cursor-pointer"
                                   title="{{ __('task.edit') }}"
                                   @click="$refs.taskTitle.attributes.contenteditable.value = 'true'; $refs.taskTitle.focus();"
                                   style="margin-left: 10px; margin-top: 5px; color: #8a8a8a"/>
            @endcan

            <x-heroicon-o-clipboard-document class="h-5 w-5 inline copy-title cursor-pointer"
                                             title="{{ __('general.copy') }}"
                                             @click="navigator.clipboard.writeText($refs.taskTitle.innerText); $wire.showNotification('{{ __('general.copied') }}');"
                                             style="margin-left: 35px; color: #8a8a8a"/>
        </span>
    </div>

    <div class="grid grid-cols-2 gap-y-8" style="margin: 35px 30px 35px 55px">
        @canany(['viewTaskCreator', 'viewAssignedUsers'], \App\Models\User::class)
            @can('viewTaskCreator', \App\Models\User::class)
                <div class="flex items-center justify-center gap-2 text-sm font-semibold">
                    <span class="text-gray-500 text-xs">{{ __('task.created_by') }}</span>
                    <div class="flex items-center gap-1">
                        @if($task->creator)
                            <img src="/storage/{{ $task->creator->avatar_url }}" alt="{{ $task->creator->name }}"
                                 title="{{ $task->creator->name }}"
                                 class="rounded-full" style="height: 20px">
                            <span>{{ $task->creator->name }}, le {{ $task->created_at->translatedFormat('d M') }}</span>
                        @else
                            <img src="{{ asset('img/avatar.png') }}" alt=""
                                 class="rounded-full" style="height: 20px">
                            <span>{{ __('general.unknown') }}</span>
                        @endif
                    </div>
                </div>
            @endcan

            @can('viewAssignedUsers', \App\Models\User::class)
                <div class="flex items-center justify-center gap-2 text-sm font-semibold">
                    <span class="text-gray-500 text-xs">{{ __('task.assign_to') }}</span>
                    <div class="flex items-center gap-1">

                        @can('assignUser', \App\Models\User::class)
                            <x-filament::dropdown>
                                <x-slot name="trigger" class="flex items-center gap-1">
                                    @forelse($task->users as $user)
                                        <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                             title="{{ $user->name }}"
                                             class="rounded-full" style="height: 20px">
                                        @if($loop->count < 2)
                                            <span>{{ $user->name }}</span>
                                        @endif
                                    @empty
                                        <img src="{{ asset('img/avatar.png') }}" alt=""
                                             class="rounded-full" style="height: 20px">
                                        <span>{{ __('task.unassigned') }}</span>
                                    @endforelse
                                </x-slot>

                                <x-filament::dropdown.list>
                                    @foreach($task->project->users as $user)
                                        <x-filament::dropdown.list.item
                                            wire:click="toggleUserToTask({{$user->id}});">
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
                            @forelse($task->users as $user)
                                <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                     title="{{ $user->name }}"
                                     class="rounded-full" style="height: 20px">
                                @if($task->users()->count() < 2)
                                    <span>{{ $user->name }}</span>
                                @endif
                            @empty
                                <img src="{{ asset('img/avatar.png') }}" alt=""
                                     class="rounded-full" style="height: 20px">
                                <span>{{ __('task.unassigned') }}</span>
                            @endforelse
                        @endcan
                    </div>
                </div>
            @endcan
        @endcanany

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">{{ __('status.status') }}</span>
            <div class="flex items-center gap-1">
                @can('changeStatus', \App\Models\User::class)
                    <x-filament::dropdown>
                        <x-slot name="trigger" class="flex items-center"
                                style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4' : '' }}">
                            @switch($task->status->name)
                                @case('À faire')
                                    <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                                style="color: {{ $task->status->color }};"/>
                                    @break
                                @case('En cours')
                                    <x-carbon-in-progress class="h-5 w-5 mx-1"
                                                          style="color: {{ $task->status->color }}"/>
                                    @break
                                @case('Terminé')
                                    <x-grommet-status-good class="h-5 w-5 mx-1"
                                                           style="color: {{ $task->status->color }}"/>
                                    @break
                                @default
                                    <x-pepicon-hourglass-circle class="h-5 w-5 mx-1"
                                                                style="color: {{ $task->status->color }}"/>
                            @endswitch
                            <span class="text-xs font-bold task-title"
                                  style="color: {{ $task->status->color }}">{{ $task->status->name }}</span>
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
                                              title="{{ $task->status->name }}">{{ \Illuminate\Support\Str::limit($status->name, 25) }}</span>
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
                    <span class="text-xs font-bold task-title"
                          style="color: {{ $task->status->color }}">{{ $task->status->name }}</span>
                @endcan
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">{{ __('priority.priority') }}</span>
            <div class="flex items-center gap-1">
                @can('changePriority', \App\Models\User::class)
                    <x-filament::dropdown>
                        <x-slot name="trigger" class="flex items-center">
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
                @else
                    <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                    <span class="text-xs font-bold task-title"
                          style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
                @endcan
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold flex-wrap">
            <span class="text-gray-500 text-xs">{{ __('task.commit_numbers') }}</span>
            @if($task->commit_numbers)
                @foreach($task->commit_numbers as $commit)
                    @can('manageCommit', \App\Models\User::class)
                        <x-filament::dropdown>
                            <x-slot name="trigger" class="flex items-center gap-1">
                                <x-gmdi-commit class="h-5 w-5" style="color: #f34f29"/>
                                <span class="text-xs font-bold">{{ $commit }}</span>
                            </x-slot>

                            <x-filament::dropdown.list>
                                <x-filament::dropdown.list.item
                                    wire:click="deleteCommitNumber({{$task->id}}, '{{ $commit }}')"
                                    x-on:click="toggle"
                                    class="text-xs font-bold">
                                    <div class="flex items-center">
                                        <div class="flex items-center" style="color: red">
                                            <x-heroicon-o-trash class="h-5 w-5 mx-1"/>
                                            <span class="mx-1">{{ __('general.delete') }}</span>
                                        </div>
                                    </div>
                                </x-filament::dropdown.list.item>
                            </x-filament::dropdown.list>

                        </x-filament::dropdown>
                    @else
                        <div class="flex items-center gap-1">
                            <x-gmdi-commit class="h-5 w-5" style="color: #f34f29"/>
                            <span class="text-xs font-bold">{{ $commit }}</span>
                        </div>
                    @endcan
                @endforeach
            @endif

            @can('manageCommit', \App\Models\User::class)
                <x-filament::icon-button
                    icon="heroicon-o-plus"
                    style="margin-left: 3px"
                    wire:click="$dispatch('openCommitModal', { taskId: '{{ $task->id }}' });"
                    tooltip="{{ __('task.add_commit_number') }}"
                />
            @endcan
        </div>

        <div class="flex items-start justify-center gap-2 text-sm font-semibold flex-wrap">
            <div class="flex items-center gap-2">
                @if($task->start_date && $task->due_date)
                    <span class="text-gray-500 text-xs">{{ __('task.dates') }}</span>
                    @can('manageDates', \App\Models\User::class)
                        <div class="flex items-center gap-1 cursor-pointer"
                             wire:click="$dispatch('openDatesModal', { taskId: '{{ $task->id }}' });">
                            <span class="font-bold">{{ $task->start_date->translatedFormat('d M') }}</span>
                            <x-heroicon-o-arrow-long-right class="h-5 w-5"/>
                            <span class="font-bold">{{ $task->due_date->translatedFormat('d M') }}</span>
                        </div>
                    @else
                        <div class="flex items-center gap-1">
                            <span class="font-bold">{{ $task->start_date->translatedFormat('d M') }}</span>
                            <x-heroicon-o-arrow-long-right class="h-5 w-5"/>
                            <span class="font-bold">{{ $task->due_date->translatedFormat('d M') }}</span>
                        </div>
                    @endcan
                @elseif($task->start_date)
                    <span class="text-gray-500 text-xs">{{ __('task.start_date') }}</span>
                    @can('manageDates', \App\Models\User::class)
                        <span class="font-bold cursor-pointer"
                              wire:click="$dispatch('openDatesModal', { taskId: '{{ $task->id }}' });">{{ $task->start_date->translatedFormat('d M') }}</span>
                    @else
                        <span class="font-bold">{{ $task->start_date->translatedFormat('d M') }}</span>
                    @endcan
                @elseif($task->due_date)
                    <span class="text-gray-500 text-xs">{{ __('task.end_date') }}</span>
                    @can('manageDates', \App\Models\User::class)
                        <span class="font-bold cursor-pointer"
                              wire:click="$dispatch('openDatesModal', { taskId: '{{ $task->id }}' });">{{ $task->due_date->translatedFormat('d M') }}</span>
                    @else
                        <span class="font-bold">{{ $task->due_date->translatedFormat('d M') }}</span>
                    @endcan
                @else
                    <span class="text-gray-500 text-xs">{{ __('task.dates') }}</span>
                    @can('manageDates', \App\Models\User::class)
                        <span class="font-bold cursor-pointer"
                              wire:click="$dispatch('openDatesModal', { taskId: '{{ $task->id }}' });">{{ __('task.no_date') }}</span>
                    @else
                        <span class="font-bold">{{ __('task.no_date') }}</span>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div x-data="{
            richIsVisible: false,
        }">
        <x-filament::section
            collapsible
            icon="heroicon-o-document-text"
            style="width: 100%">
            <x-slot name="heading">
                <span style="height: 32px">{{ __('task.form.description') }}</span>
            </x-slot>

            @can('editDescription', \App\Models\User::class)
                <x-slot name="headerEnd">
                    <x-filament::icon-button
                        icon="phosphor-pencil"
                        label="Edit description"
                        x-on:click="isCollapsed = ! isCollapsed; richIsVisible = true;"
                        tooltip="{{ __('task.edit_description') }}"
                        x-show="!richIsVisible"
                    />

                    <x-filament::button
                        class="text-xs"
                        x-show="richIsVisible"
                        x-on:click="$wire.saveRichEditorDescription({{$task}}); isCollapsed = ! isCollapsed; richIsVisible = false;">
                        {{ __('general.save') }}
                    </x-filament::button>

                    <x-filament::button
                        color="danger"
                        outlined
                        class="text-xs"
                        x-show="richIsVisible"
                        x-on:click="richIsVisible = false; isCollapsed = ! isCollapsed;">
                        {{ __('general.cancel') }}
                    </x-filament::button>
                </x-slot>

                <div class="flex flex-col justify-center items-end" x-show="richIsVisible">
                    {{ $this->richEditorFieldForm }}
                </div>
            @endcan

            <div class="flex flex-col px-6 py-3 gap-3 text-sm section-description" x-show="!richIsVisible">
                @if($task->description)
                    {!! $task->description !!}
                @else
                    <p class="text-gray-500">{{ __('task.no_description') }}</p>
                @endif
            </div>
        </x-filament::section>
    </div>

    <div class="mt-6"
         x-data="{
            fileUploadIsVisible: false,
            downloadFile(filename) {
                const link = document.createElement('a');
                link.href = '/storage/' + filename;
                link.download = filename.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }">
        <x-filament::section
            icon="heroicon-o-folder"
            badge="{{ count($task->attachments) }}"
            collapsible
            style="width: 100%; height: 100%">
            <x-slot name="heading">
                <span style="height: 32px">{{ __('task.form.attachments') }}</span>
            </x-slot>

            @can('manageAttachments', \App\Models\User::class)
                <x-slot name="headerEnd">
                    <x-filament::icon-button
                        icon="gmdi-file-upload-r"
                        class="h-5 w-5"
                        label="Upload file"
                        x-show="!fileUploadIsVisible"
                        x-on:click="$wire.fillFileUploadField(); isCollapsed = ! isCollapsed; fileUploadIsVisible = !fileUploadIsVisible;"
                        tooltip="{{ __('general.upload_file') }}"
                    />

                    <x-filament::button
                        class="text-xs"
                        x-show="fileUploadIsVisible"
                        x-on:click="$wire.saveFileUploadAttachments({{$task->id}}); isCollapsed = ! isCollapsed; fileUploadIsVisible = false;">
                        {{ __('general.save') }}
                    </x-filament::button>

                    <x-filament::button
                        color="danger"
                        outlined
                        class="text-xs"
                        x-show="fileUploadIsVisible"
                        x-on:click="$wire.cancelFileUploadAttachments; fileUploadIsVisible = false; isCollapsed = ! isCollapsed;">
                        {{ __('general.cancel') }}
                    </x-filament::button>
                </x-slot>

                <div class="flex flex-col justify-center items-end" x-show="fileUploadIsVisible">
                    {{ $this->fileUploadFieldForm }}
                </div>
            @endcan

            <div class="flex flex-col px-6 py-3 gap-3 text-sm section-description" id="taskAttachments">
                @if(count($task->attachments) > 0)
                    <div style="column-count: 4; column-gap: 10px; height: 100%">
                        @foreach($task->attachments as $attachment)
                            <x-filament::dropdown>
                                <x-slot name="trigger"
                                        style="margin: 0; display: grid; grid-template-rows: 1fr auto; margin-bottom: 10px">
                                    @switch(preg_split('/\./', $attachment)[count(preg_split('/\./', $attachment)) - 1])
                                        @case('pdf')
                                            <div
                                                class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer"
                                                style="height: 45px; word-break: break-all;">
                                                <x-tni-pdf class="h-8 w-8" style="color: #e11d21"/>
                                                <span>{{ preg_split('/\//', $attachment)[count(preg_split('/\//', $attachment)) - 1] }}</span>
                                            </div>
                                            @break
                                        @case('doc')
                                        @case('docx')
                                            <div
                                                class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer"
                                                style="height: 45px; word-break: break-all;">
                                                <x-bxs-file-txt class="h-8 w-8" style="color: #2b579a"/>
                                                <span>{{ preg_split('/\//', $attachment)[count(preg_split('/\//', $attachment)) - 1] }}</span>
                                            </div>
                                            @break
                                        @case('xls')
                                        @case('xlsx')
                                            <div
                                                class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer"
                                                style="height: 45px; word-break: break-all;">
                                                <x-fas-file-excel class="h-8 w-8" style="color: #1d9e1f"/>
                                                <span>{{ preg_split('/\//', $attachment)[count(preg_split('/\//', $attachment)) - 1] }}</span>
                                            </div>
                                            @break
                                        @case('zip')
                                        @case('rar')
                                            <div
                                                class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer"
                                                style="height: 45px; word-break: break-all;">
                                                <x-gmdi-folder-zip-r class="h-8 w-8" style="color: #f0ad4e"/>
                                                <span>{{ preg_split('/\//', $attachment)[count(preg_split('/\//', $attachment)) - 1] }}</span>
                                            </div>
                                            @break
                                        @case('jpg')
                                        @case('jpeg')
                                        @case('png')
                                        @case('webp')
                                        @case('gif')
                                            <img src="/storage/{{ $attachment }}" alt="image" style="height: 150px"
                                                 class="rounded-lg"/>
                                            @break
                                        @default
                                            <div
                                                class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer"
                                                style="height: 45px; word-break: break-all;">
                                                <x-heroicon-s-document class="h-8 w-8" style="color: #8a8a8a"/>
                                                <span>{{ preg_split('/\//', $attachment)[count(preg_split('/\//', $attachment)) - 1] }}</span>
                                            </div>
                                    @endswitch
                                </x-slot>

                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item
                                        x-on:click="window.open('/storage/{{ $attachment }}', '_blank'); toggle"
                                        class="text-xs font-bold">
                                        <div class="flex items-center">
                                            <x-heroicon-o-eye class="h-5 w-5 mx-1"/>
                                            <span class="mx-1">{{ __('general.view') }}</span>
                                        </div>
                                    </x-filament::dropdown.list.item>

                                    <x-filament::dropdown.list.item
                                        x-on:click="downloadFile('{{ $attachment }}'); toggle"
                                        class="text-xs font-bold">
                                        <div class="flex items-center">
                                            <div class="flex items-center">
                                                <x-heroicon-o-arrow-down-tray class="h-5 w-5 mx-1"/>
                                                <span class="mx-1">{{ __('general.download') }}</span>
                                            </div>
                                        </div>
                                    </x-filament::dropdown.list.item>

                                    @can('manageAttachments', \App\Models\User::class)
                                        <x-filament::dropdown.list.item
                                            x-on:click="toggle; $wire.deleteAttachment({{ $task->id}}, '{{$attachment}}')"
                                            class="text-xs font-bold">
                                            <div class="flex items-center">
                                                <div class="flex items-center" style="color: red">
                                                    <x-heroicon-o-trash class="h-5 w-5 mx-1"/>
                                                    <span class="mx-1">{{ __('general.delete') }}</span>
                                                </div>
                                            </div>
                                        </x-filament::dropdown.list.item>
                                    @endcan
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">{{ __('task.no_attachment') }}</p>
                @endif
            </div>

        </x-filament::section>
    </div>

    <div class="mt-6"
         x-init="init"
         x-data="{
            init() {
                Livewire.on('commentSent', () => {
                    $nextTick(() => {
                        document.getElementById('comment').scrollIntoView({behavior: 'smooth'});
                    });
                });
            }
        }">
        <x-filament::section
            collapsible
            icon="uni-comment-alt-lines-o"
            style="width: 100%">
            <x-slot name="heading">
                <span style="height: 32px">{{ __('task.comments') }}</span>
            </x-slot>

            <div id="comments" class="comments flex flex-col px-6 py-3 gap-3 text-sm section-description">
                @if(count($task->comments) > 0)
                    @foreach($task->comments()->withTrashed()->get() as $comment)
                        <div class="flex items-start gap-2.5" style="{{ $comment->trashed() ? 'opacity: 0.5' : '' }}">
                            @if(auth()->user()->hasRole('Client') && $comment->user->id != auth()->user()->id)
                                <img class="w-8 h-8 rounded-full" src="{{ asset('img/avatar.png') }}" alt="avatar">
                            @else
                                <img class="w-8 h-8 rounded-full" src="/storage/{{ $comment->user->avatar_url }}"
                                     alt="{{ $comment->user->name }}">
                            @endif
                            <div class="flex flex-col gap-1 w-auto">
                                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                    <span
                                        class="text-sm font-semibold">{{ auth()->user()->hasRole('Client') && $comment->user->id != auth()->user()->id ? __('user.user') : $comment->user->name }}</span>
                                    <span
                                        class="text-xs font-normal">{{ $comment->created_at->diffForHumans() }}</span>
                                    @if($comment->trashed())
                                        <x-filament::badge color="danger">
                                            {{ __('general.deleted') }}
                                        </x-filament::badge>
                                    @endif
                                </div>
                                <div class="flex items-center">
                                    <div
                                        class="flex flex-col leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700">
                                        <p class="">{!! $comment->content !!}</p>
                                    </div>

                                    @if(!$comment->trashed())
                                        @if(($comment->user->id == auth()->user()->id) || (auth()->user()->hasPermission('delete_any_comment')))
                                            <x-filament::dropdown placement="right">
                                                <x-slot name="trigger">
                                                    <div class="px-2">
                                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400"
                                                             aria-hidden="true"
                                                             xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                                             viewBox="0 0 4 15">
                                                            <path
                                                                d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                                        </svg>
                                                    </div>
                                                </x-slot>

                                                <x-filament::dropdown.list>
                                                    <x-filament::dropdown.list.item
                                                        x-on:click="toggle; $wire.deleteComment({{ $comment->id }})"
                                                        class="text-xs font-bold">
                                                        <div class="flex items-center">
                                                            <div class="flex items-center" style="color: red">
                                                                <x-heroicon-o-trash class="h-5 w-5 mx-1"/>
                                                                <span class="mx-1">{{ __('general.delete') }}</span>
                                                            </div>
                                                        </div>
                                                    </x-filament::dropdown.list.item>
                                                </x-filament::dropdown.list>
                                            </x-filament::dropdown>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-gray-500 mt-3">{{ __('task.no_comment') }}</p>
                @endif
            </div>

            @can('addComment', \App\Models\User::class)
                <div id="input-comment">
                    <div class="flex px-3 py-2 rounded-lg bg-transparent gap-2">
                        <img class="w-8 h-8 rounded-full my-2" src="/storage/{{ auth()->user()->avatar_url }}"
                             alt="{{ auth()->user()->name }}">

                        {{ $this->commentRichEditorFieldForm }}

                        <a
                            title="{{ __('general.send') }} (Shift+Enter)"
                            wire:click="sendComment({{ $task->id }})"
                            class="inline-flex justify-center items-center my-2 p-2 text-blue-600 rounded-full cursor-pointer hover:opacity-50 dark:text-blue-500 w-8 h-8">
                            <svg class="w-5 h-5 rotate-90 rtl:-rotate-90" aria-hidden="true"
                                 xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                                <path
                                    d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z"/>
                            </svg>
                            <span class="sr-only">{{ __('general.send') }}</span>
                        </a>
                    </div>
                </div>
            @endcan

        </x-filament::section>
    </div>

    <x-filament-actions::modals/>


    <style>
        .fi-modal-footer-actions {
            justify-content: space-between;
        }
    </style>
</div>
