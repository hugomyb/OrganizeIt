<div class="flex flex-col items-end">
    <ul style="margin-left: 8px; width: 100%">
        @forelse($tasks as $task)
            <li>- {{ $task->title }}</li>
        @empty
            <li class="text-center">{{ __('task.no_tasks') }}</li>
        @endforelse
    </ul>

    <div class="flex w-full items-center justify-center my-2">
        <x-filament::loading-indicator class="h-5 w-5" wire:loading/>
    </div>

    <textarea wire:loading.remove class="w-full mt-4 p-2" rows="5" hidden
              readonly>- {{ $tasks->pluck('title')->join("\n- ") }}</textarea>

    @if($tasks->count())
        <x-filament::button
            class="mt-4"
            wire:click="showNotification('{{ __('task.copied_tasks') }}')"
            onclick="navigator.clipboard.writeText(document.querySelector('textarea').value);">
            {{ __('task.copy_tasks') }}
        </x-filament::button>
    @endif
</div>
