<div class="flex items-start gap-2.5" style="{{ $comment->trashed() ? 'opacity: 0.5' : '' }}">
    @if(auth()->user()->hasRole('Client') && $comment->user->id != auth()->user()->id)
        <img class="w-8 h-8 rounded-full" src="{{ asset('img/avatar.png') }}" alt="avatar">
    @else
        <img class="w-8 h-8 rounded-full" src="/storage/{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}">
    @endif
    <div class="flex flex-col gap-1 w-auto">
        <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <span class="text-sm font-semibold">{{ auth()->user()->hasRole('Client') && $comment->user->id != auth()->user()->id ? __('user.user') : $comment->user->name }}</span>
            <span class="text-xs font-normal">{{ $comment->created_at->diffForHumans() }}</span>
            @if($comment->trashed())
                <x-filament::badge color="danger">{{ __('general.deleted') }}</x-filament::badge>
            @endif
        </div>
        <div class="flex items-center">
            <div class="flex flex-col leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700">
                <p class="text-sm font-normal">{{ $comment->content }}</p>
            </div>

            @if(!$comment->trashed() && (auth()->user()->id == $comment->user->id || auth()->user()->can('delete_any_comment')))
                <x-filament::dropdown placement="right">
                    <x-slot name="trigger">
                        <div class="px-2">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
                                <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                            </svg>
                        </div>
                    </x-slot>

                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item x-on:click="toggle; $wire.deleteComment({{ $comment->id }})" class="text-xs font-bold">
                            <div class="flex items-center" style="color: red">
                                <x-heroicon-o-trash class="h-5 w-5 mx-1"/>
                                <span class="mx-1">{{ __('general.delete') }}</span>
                            </div>
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif
        </div>
    </div>
</div>
