<div class="flex flex-col"
     x-data="
     {
        richIsVisible: false,
        fileUploadIsVisible: false,

        init() {
            this.updateUrl('{{ $task->id }}');
            this.preventEnterInTitle();
            this.setupCommentEvent();
        },
        updateUrl(taskId) {
            const url = new URL(window.location);
            url.searchParams.set('task', taskId);
            window.history.pushState({}, '', url);
        },
        preventEnterInTitle() {
            document.getElementById('titleEditable').addEventListener('keypress', (evt) => {
                if (evt.which === 13) {
                    evt.preventDefault();
                }
            });
        },
        setupCommentEvent() {
            Livewire.on('commentSent', () => {
                this.$nextTick(() => {
                    document.getElementById('comment').scrollIntoView({behavior: 'smooth'});
                });
            });
        },
        shareLink() {
            navigator.clipboard.writeText(window.location.href);
            $wire.showNotification('{{ __('general.link_copied') }}');
        },
        editTitle() {
            this.$refs.taskTitle.setAttribute('contenteditable', 'true');
            this.$refs.taskTitle.focus();
        },
        saveTaskTitle(event) {
            $wire.saveTaskTitle(taskId, event.target.innerText);
        },
        copyTitle() {
            navigator.clipboard.writeText(this.$refs.taskTitle.innerText);
            $wire.showNotification('{{ __('general.copied') }}');
        },
        toggleDescriptionEditor() {
            this.richIsVisible = !this.richIsVisible;
            this.isCollapsed = !this.isCollapsed;
        },
        saveDescription() {
            $wire.saveRichEditorDescription(taskId);
            this.toggleDescriptionEditor();
        },
        toggleFileUpload() {
            this.fileUploadIsVisible = !this.fileUploadIsVisible;
            this.isCollapsed = !this.isCollapsed;
        },
        saveFileUpload() {
            $wire.saveFileUploadAttachments(taskId);
            this.toggleFileUpload();
        },
        cancelFileUpload() {
            $wire.cancelFileUploadAttachments();
            this.toggleFileUpload();
        }
    }"
>
    <div class="flex items-center justify-between">
        @include('infolists.components.breadcrumb-entry', ['record' => $task])
        <x-filament::button outlined icon="phosphor-share" color="primary" class="ml-auto" size="sm"
                            x-on:click="shareLink">
            {{ __('general.share') }}
        </x-filament::button>
    </div>

    <div class="flex items-center gap-3 mt-6 border-b border-gray-100 dark:border-gray-700 pb-6 task-title">
        @if($task->creator && auth()->user()->can('view_task_creator'))
            <img src="/storage/{{ $task->creator->avatar_url }}" alt="{{ $task->creator->name }}"
                 title="{{ $task->creator->name }}" class="rounded-full" style="height: 60px">
        @else
            <img class="rounded-full" src="{{ asset('img/avatar.png') }}" alt="avatar" style="height: 60px">
        @endif

        <span class="text-xl font-semibold relative inline-block" id="titleEditable" x-ref="taskTitle"
              contenteditable="false" x-on:blur="saveTaskTitle" @keyup.enter.prevent="$event.target.blur()">
            {{ $task->title }}

            @can('manageTasks', \App\Models\User::class)
                <x-phosphor-pencil class="h-5 w-5 absolute inline edit-title cursor-pointer"
                                   title="{{ __('task.edit') }}" @click="editTitle"
                                   style="margin-left: 10px; margin-top: 5px; color: #8a8a8a"/>
            @endcan

            <x-heroicon-o-clipboard-document class="h-5 w-5 inline copy-title cursor-pointer"
                                             title="{{ __('general.copy') }}" @click="copyTitle"
                                             style="margin-left: 35px; color: #8a8a8a"/>
        </span>
    </div>

    <div class="grid grid-cols-2 gap-y-8" style="margin: 35px 30px 35px 55px">
        @include('tasks.partials.creator-and-assignees', ['task' => $task])

        @include('tasks.partials.status', ['task' => $task])

        @include('tasks.partials.priority', ['task' => $task])

        @include('tasks.partials.commit-numbers', ['task' => $task])

        @include('tasks.partials.dates', ['task' => $task])
    </div>

    <x-filament::section collapsible icon="heroicon-o-document-text" style="width: 100%">
        <x-slot name="heading">
            <span style="height: 32px">{{ __('task.form.description') }}</span>
        </x-slot>

        @can('editDescription', \App\Models\User::class)
            <x-slot name="headerEnd">
                <x-filament::icon-button icon="phosphor-pencil" label="Edit description"
                                         x-on:click="toggleDescriptionEditor"
                                         tooltip="{{ __('task.edit_description') }}" x-show="!richIsVisible"/>
                <x-filament::button class="text-xs" x-show="richIsVisible"
                                    x-on:click="saveDescription">{{ __('general.save') }}</x-filament::button>
                <x-filament::button color="danger" outlined class="text-xs" x-show="richIsVisible"
                                    x-on:click="toggleDescriptionEditor">{{ __('general.cancel') }}</x-filament::button>
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

    <x-filament::section icon="heroicon-o-folder" badge="{{ count($task->attachments) }}" collapsible
                         style="width: 100%">
        <x-slot name="heading">
            <span style="height: 32px">{{ __('task.form.attachments') }}</span>
        </x-slot>

        @can('manageAttachments', \App\Models\User::class)
            <x-slot name="headerEnd">
                <x-filament::icon-button icon="gmdi-file-upload-r" class="h-5 w-5" label="Upload file"
                                         x-show="!fileUploadIsVisible" x-on:click="toggleFileUpload"
                                         tooltip="{{ __('general.upload_file') }}"/>
                <x-filament::button class="text-xs" x-show="fileUploadIsVisible"
                                    x-on:click="saveFileUpload">{{ __('general.save') }}</x-filament::button>
                <x-filament::button color="danger" outlined class="text-xs" x-show="fileUploadIsVisible"
                                    x-on:click="cancelFileUpload">{{ __('general.cancel') }}</x-filament::button>
            </x-slot>

            <div class="flex flex-col justify-center items-end" x-show="fileUploadIsVisible">
                {{ $this->fileUploadFieldForm }}
            </div>
        @endcan

        <div class="flex flex-col px-6 py-3 gap-3 text-sm section-description" id="taskAttachments">
            @if(count($task->attachments) > 0)
                <div style="column-count: 4; column-gap: 10px;">
                    @foreach($task->attachments as $attachment)
                        @include('tasks.partials.attachment', ['attachment' => $attachment, 'task' => $task])
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">{{ __('task.no_attachment') }}</p>
            @endif
        </div>
    </x-filament::section>

    <x-filament::section collapsible icon="uni-comment-alt-lines-o" style="width: 100%">
        <x-slot name="heading">
            <span style="height: 32px">{{ __('task.comments') }}</span>
        </x-slot>

        <div id="comments" class="comments flex flex-col px-6 py-3 gap-3 text-sm section-description">
            @if(count($task->comments) > 0)
                @foreach($task->comments()->withTrashed()->get() as $comment)
                    @include('tasks.partials.comment', ['comment' => $comment])
                @endforeach
            @else
                <p class="text-gray-500 mt-3">{{ __('task.no_comment') }}</p>
            @endif
        </div>

        @can('addComment', \App\Models\User::class)
            <div id="input-comment">
                <div class="flex items-center px-3 py-2 rounded-lg bg-transparent gap-2">
                    <img class="w-8 h-8 rounded-full" src="/storage/{{ auth()->user()->avatar_url }}"
                         alt="{{ auth()->user()->name }}">
                    <textarea id="comment" rows="1" wire:model="comment"
                              @keyup.shift.enter="$wire.sendComment({{ $task->id }})"
                              class="block mx-4 p-2.5 w-full text-sm bg-white rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500"
                              placeholder="{{ __('task.your_comment') }}"></textarea>
                    <a title="{{ __('general.send') }} (Shift+Enter)" wire:click="sendComment({{ $task->id }})"
                       class="inline-flex justify-center p-2 text-blue-600 rounded-full cursor-pointer hover:bg-blue-100 dark:text-blue-500 dark:hover:bg-gray-600">
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
