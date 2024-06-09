<x-filament-widgets::widget>
    <x-filament::section style="padding: 0 !important">
        <x-slot name="heading">
            <h2 class="text-lg font-semibold">{{ __('project.recents') }}</h2>
        </x-slot>

        @forelse($projects as $project)
            <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('show', ['record' => $project]) }}" class="block">
                <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-5 h-5 rounded-full"
                             style="background-color: {{ $project->color }}; margin-right: 15px"></div>
                        <div>
                            <h3 class="text-sm font-medium">{{ $project->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $project->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="px-4 py-3 text-gray-500 text-sm dark:text-gray-400">
                {{ __('widgets.no_recent_projects') }}
            </div>
        @endforelse
    </x-filament::section>

    <style>
        .fi-section-content {
            padding: 8px !important;
        }
    </style>
</x-filament-widgets::widget>
