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
                        <x-filament::dropdown.list.item wire:click="deleteCommitNumber({{$task->id}}, '{{ $commit }}')" x-on:click="toggle" class="text-xs font-bold">
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
        <x-filament::icon-button icon="heroicon-o-plus" style="margin-left: 3px" x-on:click="$wire.replaceMountedAction('addCommit', {task: {{ $task->id }}});" tooltip="{{ __('task.add_commit_number') }}"/>
    @endcan
</div>
