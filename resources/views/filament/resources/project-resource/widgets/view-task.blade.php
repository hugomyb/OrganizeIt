<div class="flex flex-col">
    @include('infolists.components.breadcrumb-entry', ['record' => $task])

    <div class="flex items-center gap-3 mt-6 border-b border-gray-100 dark:border-gray-700 pb-6">
        @if($task->creator)
            <img src="{{ asset($task->creator->avatar) }}" alt="{{ $task->creator->name }}"
                 class="rounded-full" style="height: 60px">
        @else
            <img class="rounded-full" src="{{ asset('img/avatar.png') }}" alt="avatar" style="height: 60px">
        @endif

        <span class="text-xl font-semibold">{{ $task->title }}</span>
    </div>
</div>
