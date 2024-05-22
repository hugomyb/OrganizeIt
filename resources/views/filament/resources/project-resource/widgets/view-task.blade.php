<div class="flex flex-col">
    @include('infolists.components.breadcrumb-entry', ['record' => $task])

    <div
        class="flex items-center gap-3 mt-6 border-b border-gray-100 dark:border-gray-700 pb-6 task-title">
        @if($task->creator)
            <img src="{{ asset($task->creator->avatar) }}" alt="{{ $task->creator->name }}"
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
              x-on:blur="$wire.saveTaskTitle({{ $task->id }}, $event.target.innerText)"
              @keyup.enter.prevent="$event.target.blur()">
            {{ $task->title }}

            <x-phosphor-pencil class="h-5 w-5 absolute inline edit-title cursor-pointer" title="Éditer"
                               @click="$refs.taskTitle.attributes.contenteditable.value = 'true'; $refs.taskTitle.focus();"
                               style="margin-left: 10px; margin-top: 5px; color: #8a8a8a"/>

            <x-heroicon-o-clipboard-document class="h-5 w-5 inline copy-title cursor-pointer" title="Copier"
                                             @click="navigator.clipboard.writeText($refs.taskTitle.innerText); $wire.showNotification('Copié dans le presse-papier');"
                                             style="margin-left: 35px; color: #8a8a8a"/>
        </span>
    </div>

    <div class="grid grid-cols-2 gap-y-8" style="margin: 35px 30px 35px 55px">
        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">Créé par</span>
            <div class="flex items-center gap-1">
                @if($task->creator)
                    <img src="{{ asset($task->creator->avatar) }}" alt="{{ $task->creator->name }}"
                         title="{{ $task->creator->name }}"
                         class="rounded-full" style="height: 20px">
                    <span>{{ $task->creator->name }}, le {{ $task->created_at->translatedFormat('d M') }}</span>
                @else
                    <img src="{{ asset('img/avatar.png') }}" alt=""
                         class="rounded-full" style="height: 20px">
                    <span>Inconnu</span>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">Assigné à</span>
            <div class="flex items-center gap-1">
                <x-filament::dropdown>
                    <x-slot name="trigger" class="flex items-center gap-1">
                        @forelse($task->users as $user)
                            <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}"
                                 title="{{ $user->name }}"
                                 class="rounded-full" style="height: 20px">
                            @if($task->users()->count() < 2)
                                <span>{{ $user->name }}</span>
                            @endif
                        @empty
                            <img src="{{ asset('img/avatar.png') }}" alt=""
                                 class="rounded-full" style="height: 20px">
                            <span>Non assigné</span>
                        @endforelse
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach($task->project->users as $user)
                            <x-filament::dropdown.list.item
                                wire:click="toggleUserToTask({{$user->id}}, {{$task->id}})">
                                <div class="text-xs font-bold flex justify-between items-center">
                                    <div class="flex items center gap-1 items-center">
                                        <img src="/storage/{{ $user->avatar }}" alt="{{ $user->name }}"
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
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">Statut</span>
            <div class="flex items-center gap-1">
                <x-filament::dropdown>
                    <x-slot name="trigger" class="flex items-center"
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
                        <span class="text-xs font-bold task-title"
                              style="color: {{ $task->status->color }}">{{ $task->status->name }}</span>
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
                                    <span class="mx-1">{{ $status->name }}</span>
                                </div>
                            </x-filament::dropdown.list.item>
                        @endforeach
                    </x-filament::dropdown.list>

                </x-filament::dropdown>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">Priorité</span>
            <div class="flex items-center gap-1">
                <x-filament::dropdown>
                    <x-slot name="trigger" class="flex items-center">
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
                <span style="height: 32px">Description</span>
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::icon-button
                    icon="phosphor-pencil"
                    label="Edit description"
                    x-on:click="$wire.fillRichEditorField({{$task}}); isCollapsed = ! isCollapsed; richIsVisible = true;"
                    tooltip="Éditer la description"
                    x-show="!richIsVisible"
                />

                <x-filament::button
                    class="text-xs"
                    x-show="richIsVisible"
                    x-on:click="$wire.saveRichEditorDescription({{$task}}); isCollapsed = ! isCollapsed; richIsVisible = false;">
                    Sauvegarder
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    outlined
                    class="text-xs"
                    x-show="richIsVisible"
                    x-on:click="$wire.cancelRichEditorDescription; richIsVisible = false; isCollapsed = ! isCollapsed;">
                    Annuler
                </x-filament::button>
            </x-slot>

            <div class="flex flex-col justify-center items-end" x-show="richIsVisible">
                {{ $this->richEditorFieldForm }}
            </div>

            <div class="flex flex-col px-6 py-3 gap-3 text-sm section-description" x-show="!richIsVisible">
                @if($task->description)
                    {!! $task->description !!}
                @else
                    <p class="text-gray-500">Aucune description</p>
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
            style="width: 100%">
            <x-slot name="heading">
                <span style="height: 32px">Pièces jointes </span>
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::icon-button
                    icon="gmdi-file-upload-r"
                    class="h-5 w-5"
                    label="Upload file"
                    x-show="!fileUploadIsVisible"
                    x-on:click="$wire.fillFileUploadField({{ $task->id }}); isCollapsed = ! isCollapsed; fileUploadIsVisible = !fileUploadIsVisible;"
                    tooltip="Uploader un fichier"
                />

                <x-filament::button
                    class="text-xs"
                    x-show="fileUploadIsVisible"
                    x-on:click="$wire.saveFileUploadAttachments({{$task->id}}); isCollapsed = ! isCollapsed; fileUploadIsVisible = false;">
                    Sauvegarder
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    outlined
                    class="text-xs"
                    x-show="fileUploadIsVisible"
                    x-on:click="$wire.cancelFileUploadAttachments; fileUploadIsVisible = false; isCollapsed = ! isCollapsed;">
                    Annuler
                </x-filament::button>
            </x-slot>

            <div class="flex flex-col justify-center items-end" x-show="fileUploadIsVisible">
                {{ $this->fileUploadFieldForm }}
            </div>

            <div class="flex flex-col px-6 py-3 gap-3 text-sm section-description">
                @if(count($task->attachments) > 0)
                    <div style="column-count: 4; column-gap: 10px;">
                        @foreach($task->attachments as $attachment)
                            <x-filament::dropdown>
                                <x-slot name="trigger" style="margin: 0; display: grid; grid-template-rows: 1fr auto; margin-bottom: 10px">
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
                                            <span class="mx-1">Voir</span>
                                        </div>
                                    </x-filament::dropdown.list.item>

                                    <x-filament::dropdown.list.item
                                        x-on:click="downloadFile('{{ $attachment }}'); toggle"
                                        class="text-xs font-bold">
                                        <div class="flex items-center">
                                            <div class="flex items-center">
                                                <x-heroicon-o-arrow-down-tray class="h-5 w-5 mx-1"/>
                                                <span class="mx-1">Télécharger</span>
                                            </div>
                                        </div>
                                    </x-filament::dropdown.list.item>

                                    <x-filament::dropdown.list.item
                                        x-on:click="toggle; $wire.deleteAttachment({{ $task->id}}, '{{$attachment}}')"
                                        class="text-xs font-bold">
                                        <div class="flex items-center">
                                            <div class="flex items-center" style="color: red">
                                                <x-heroicon-o-trash class="h-5 w-5 mx-1"/>
                                                <span class="mx-1">Supprimer</span>
                                            </div>
                                        </div>
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Aucune pièce jointe</p>
                @endif
            </div>

        </x-filament::section>
    </div>
</div>
