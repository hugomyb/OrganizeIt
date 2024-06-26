<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <h2 class="text-lg font-semibold">{{ __('widgets.latest_tasks_widget') }}</h2>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::icon-button
                color="gray"
                icon="heroicon-o-information-circle"
                tooltip="{{ __('widgets.info') }}"/>
        </x-slot>

        @forelse($tasks as $task)
            <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $task->project, 'task' => $task->id]) }}"
               class="block">
                <div
                    class="flex items-center justify-between px-4 py-3 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl">
                    <!-- left side -->
                    <div class="flex flex-col items-start gap-2">
                        <!-- left top -->
                        <div class="flex gap-1.5 items-center">
                            <div class="flex justify-start gap-1.5">
                                <!-- status -->
                                <div title="{{ $task->status->name }}">
                                    @switch($task->status->name)
                                        @case('À faire')
                                            <x-pepicon-hourglass-circle class="h-5 w-5"
                                                                        style="color: {{ $task->status->color }};"/>
                                            @break
                                        @case('En cours')
                                            <x-carbon-in-progress class="h-5 w-5"
                                                                  style="color: {{ $task->status->color }}"/>
                                            @break
                                        @case('Terminé')
                                            <x-grommet-status-good class="h-5 w-5"
                                                                   style="color: {{ $task->status->color }}"/>
                                            @break
                                        @default
                                            <x-pepicon-hourglass-circle class="h-5 w-5"
                                                                        style="color: {{ $task->status->color }}"/>
                                    @endswitch
                                </div>
                                <!-- priority -->
                                <div title="{{ $task->priority->name }}">
                                    <x-iconsax-bol-flag-2 class="h-5 w-5" style="color: {{ $task->priority->color }}"/>
                                </div>
                            </div>
                            <p class="text-sm font-semibold"
                               title="{{ $task->title }}">{{ \Illuminate\Support\Str::limit($task->title, 40) }}</p>
                        </div>
                        <!-- left bottom -->
                        <div class="flex justify-start items-center">
                            <div class="flex-shrink-0 w-3.5 h-3.5 rounded-full"
                                 style="background-color: {{ $task->project->color }}; margin-right: 10px"></div>
                            <div>
                                <p class="text-xs font-medium">{{ $task->project->name }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- right side -->
                    <div class="flex items-center space-x-4">
                        @if(auth()->user()->hasPermission('view_assigned_users'))
                            @if($task->creator)
                                <img src="/storage/{{ $task->creator->avatar_url }}" alt="{{ $task->creator->name }}"
                                     title="{{ __('task.created_by') . ' ' . $task->creator->name }}"
                                     class="rounded-full h-6">
                            @else
                                <img class="rounded-full h-6"
                                     title="{{ __('task.created_by') . ' ' . __('general.unknown')  }}"
                                     src="{{ asset('img/avatar.png') }}" alt="avatar">
                            @endif
                        @endif
                        <p class="text-xs text-gray-500">{{ $task->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                {{ __('widgets.no_recent_tasks') }}
            </div>
        @endforelse

    </x-filament::section>
</x-filament-widgets::widget>
