<x-filament::dropdown>
    <x-slot name="trigger" style="margin: 0; display: grid; grid-template-rows: 1fr auto; margin-bottom: 10px">
        @switch(pathinfo($attachment, PATHINFO_EXTENSION))
            @case('pdf')
                <div class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer" style="height: 45px; word-break: break-all;">
                    <x-tni-pdf class="h-8 w-8" style="color: #e11d21"/>
                    <span>{{ basename($attachment) }}</span>
                </div>
                @break
            @case('doc')
            @case('docx')
                <div class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer" style="height: 45px; word-break: break-all;">
                    <x-bxs-file-txt class="h-8 w-8" style="color: #2b579a"/>
                    <span>{{ basename($attachment) }}</span>
                </div>
                @break
            @case('xls')
            @case('xlsx')
                <div class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer" style="height: 45px; word-break: break-all;">
                    <x-fas-file-excel class="h-8 w-8" style="color: #1d9e1f"/>
                    <span>{{ basename($attachment) }}</span>
                </div>
                @break
            @case('zip')
            @case('rar')
                <div class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer" style="height: 45px; word-break: break-all;">
                    <x-gmdi-folder-zip-r class="h-8 w-8" style="color: #f0ad4e"/>
                    <span>{{ basename($attachment) }}</span>
                </div>
                @break
            @case('jpg')
            @case('jpeg')
            @case('png')
            @case('webp')
            @case('gif')
                <img src="/storage/{{ $attachment }}" alt="image" style="height: 150px" class="rounded-lg"/>
                @break
            @default
                <div class="flex items-center gap-1 my-1 hover:bg-gray-100 dark:hover:bg-white/5 p-2 rounded-lg cursor-pointer" style="height: 45px; word-break: break-all;">
                    <x-heroicon-s-document class="h-8 w-8" style="color: #8a8a8a"/>
                    <span>{{ basename($attachment) }}</span>
                </div>
        @endswitch
    </x-slot>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item x-on:click="window.open('/storage/{{ $attachment }}', '_blank'); toggle" class="text-xs font-bold">
            <div class="flex items-center">
                <x-heroicon-o-eye class="h-5 w-5 mx-1"/>
                <span class="mx-1">{{ __('general.view') }}</span>
            </div>
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item x-on:click="downloadFile('{{ $attachment }}'); toggle" class="text-xs font-bold">
            <div class="flex items-center">
                <x-heroicon-o-arrow-down-tray class="h-5 w-5 mx-1"/>
                <span class="mx-1">{{ __('general.download') }}</span>
            </div>
        </x-filament::dropdown.list.item>

        @can('manageAttachments', \App\Models\User::class)
            <x-filament::dropdown.list.item x-on:click="toggle; $wire.deleteAttachment({{ $task->id}}, '{{$attachment}}')" class="text-xs font-bold">
                <div class="flex items-center" style="color: red">
                    <x-heroicon-o-trash class="h-5 w-5 mx-1"/>
                    <span class="mx-1">{{ __('general.delete') }}</span>
                </div>
            </x-filament::dropdown.list.item>
        @endcan
    </x-filament::dropdown.list>
</x-filament::dropdown>
