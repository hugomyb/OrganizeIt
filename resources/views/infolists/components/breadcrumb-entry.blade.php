<nav class="fi-breadcrumbs">
    <ol class="fi-breadcrumbs-list flex flex-wrap items-center gap-x-2">

        <li class="fi-breadcrumbs-item flex gap-x-2">
            <a href="javascript:void(0)"
               class="fi-breadcrumbs-item-label text-sm font-medium text-gray-500 transition duration-75 dark:text-gray-400 cursor-default"
            >
                <div class="flex items-center gap-2">
                    <div class="flex-shrink-0 w-4 h-4 rounded-full"
                         style="background-color: {{ $record->project->color }};">
                    </div>
                    <span>{{ $record->project->name }}</span>
                </div>
            </a>
        </li>

        <li class="fi-breadcrumbs-item flex gap-x-2">
            <x-filament::icon
                alias="breadcrumbs.separator"
                icon="heroicon-m-chevron-right"
                @class([
                    'rtl:hidden',
                ])
                :style="'width: 1.25rem; opacity: 0.6'"
            />

            <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $record->project->id]) }}"
               class="fi-breadcrumbs-item-label text-sm font-medium text-gray-500 transition duration-75 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <div class="flex items-center gap-2">
                    <x-fas-tasks class="h-4 w-4" :style="'color: #2563eb'"/>

                    <span>{{ __('task.tasks') }}</span>
                </div>
            </a>
        </li>

        <li class="fi-breadcrumbs-item flex gap-x-2">
            <x-filament::icon
                alias="breadcrumbs.separator"
                icon="heroicon-m-chevron-right"
                @class([
                    'rtl:hidden',
                ])
                :style="'width: 1.25rem; opacity: 0.6'"
            />

            <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $record->project->id]) . '#group-' . $record->group->id }}"
               wire:click="unmountAction('viewTaskAction', { 'task_id': '{{$record->id}}' })"
               class="fi-breadcrumbs-item-label text-sm font-medium text-gray-500 transition duration-75 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <div class="flex items-center gap-2">
                    <span>{{ $record->group->name }}</span>
                </div>
            </a>
        </li>
    </ol>
</nav>
