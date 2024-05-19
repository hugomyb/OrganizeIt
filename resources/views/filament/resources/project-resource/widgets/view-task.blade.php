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
                    <span>Aucun</span>
                @endforelse
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">Statut</span>
            <div class="flex items-center gap-1">
                @switch($task->status->name)
                    @case('À faire')
                        <x-pepicon-hourglass-circle class="h-5 w-5 mx-1" style="color: {{ $task->status->color }};"/>
                        @break
                    @case('En cours')
                        <x-carbon-in-progress class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                        @break
                    @case('Terminé')
                        <x-grommet-status-good class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                        @break
                    @default
                        <x-pepicon-hourglass-circle class="h-5 w-5 mx-1" style="color: {{ $task->status->color }}"/>
                @endswitch
                <span class="text-xs font-bold task-title"
                      style="color: {{ $task->status->color }}">{{ $task->status->name }}</span>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">Priorité</span>
            <div class="flex items-center gap-1">
                <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                <span class="text-xs font-bold task-title"
                      style="color: {{ $task->priority->color }}">{{ $task->priority->name }}</span>
            </div>
        </div>
    </div>

    <x-filament::section
        collapsible
        style="width: 100%">
        <x-slot name="heading">
            Description
        </x-slot>

        <div class="flex flex-col px-6 py-3 gap-3 text-sm">
            {!! $task->description !!}
        </div>
    </x-filament::section>
</div>
