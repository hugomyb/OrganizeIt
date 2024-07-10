<div class="flex items-center justify-center gap-2 text-sm font-semibold">
    <span class="text-gray-500 text-xs">{{ __('status.status') }}</span>
    <div class="flex items-center gap-1">
        @can('changeStatus', \App\Models\User::class)
            <x-filament::dropdown>
                <x-slot name="trigger" class="flex items-center" style="{{ $task->status->id == \App\Models\Status::where('name', 'Terminé')->first()->id ? 'opacity: 0.4' : '' }}">
                    @include('tasks.partials.status-icon', ['status' => $task->status])
                    <span class="text-xs font-bold task-title" style="color: {{ $task->status->color }}">{{ $task->status->name }}</span>
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach(\App\Models\Status::all() as $status)
                        <x-filament::dropdown.list.item wire:click="setTaskStatus({{$task->id}}, {{$status->id}})" x-on:click="toggle" class="text-xs font-bold">
                            <div class="flex items-center">
                                @include('tasks.partials.status-icon', ['status' => $status])
                                <span class="mx-1" title="{{ $task->status->name }}">{{ \Illuminate\Support\Str::limit($status->name, 25) }}</span>
                            </div>
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @else
            @include('tasks.partials.status-icon', ['status' => $task->status])
            <span class="text-xs font-bold task-title" style="color: {{ $task->status->color }}">{{ $task->status->name }}</span>
        @endcan
    </div>
</div>
