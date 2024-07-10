@canany(['viewTaskCreator', 'viewAssignedUsers'], \App\Models\User::class)
    @can('viewTaskCreator', \App\Models\User::class)
        <div class="flex items-center justify-center gap-2 text-sm font-semibold">
            <span class="text-gray-500 text-xs">{{ __('task.created_by') }}</span>
            <div class="flex items-center gap-1">
                @if($task->creator)
                    <img src="/storage/{{ $task->creator->avatar_url }}" alt="{{ $task->creator->name }}" title="{{ $task->creator->name }}" class="rounded-full" style="height: 20px">
                    <span>{{ $task->creator->name }}, le {{ $task->created_at->translatedFormat('d M') }}</span>
                @else
                    <img src="{{ asset('img/avatar.png') }}" alt="" class="rounded-full" style="height: 20px">
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
                                <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}" title="{{ $user->name }}" class="rounded-full" style="height: 20px">
                                @if($task->users()->count() < 2)
                                    <span>{{ $user->name }}</span>
                                @endif
                            @empty
                                <img src="{{ asset('img/avatar.png') }}" alt="" class="rounded-full" style="height: 20px">
                                <span>{{ __('task.unassigned') }}</span>
                            @endforelse
                        </x-slot>

                        <x-filament::dropdown.list>
                            @foreach($task->project->users as $user)
                                <x-filament::dropdown.list.item wire:click="toggleUserToTask({{$user->id}}, {{$task->id}})">
                                    <div class="text-xs font-bold flex justify-between items-center">
                                        <div class="flex items-center gap-1">
                                            <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-5 h-5 rounded-full border-1 border-white dark:border-gray-900 dark:hover:border-white/10">
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
                        <img src="/storage/{{ $user->avatar_url }}" alt="{{ $user->name }}" title="{{ $user->name }}" class="rounded-full" style="height: 20px">
                        @if($task->users()->count() < 2)
                            <span>{{ $user->name }}</span>
                        @endif
                    @empty
                        <img src="{{ asset('img/avatar.png') }}" alt="" class="rounded-full" style="height: 20px">
                        <span>{{ __('task.unassigned') }}</span>
                    @endforelse
                @endcan
            </div>
        </div>
    @endcan
@endcanany
