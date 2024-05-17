<div class="flex items-center h-full gap-2">
    @switch($status->name)
        @case('À faire')
            <x-far-circle class="h-5 w-5" style="color: {{ $status->color }}"/>
            @break
        @case('En cours')
            <x-carbon-in-progress class="h-5 w-5" style="color: {{ $status->color }}"/>
            @break
        @case('Terminé')
            <x-grommet-status-good class="h-5 w-5"
                                   style="color: {{ $status->color }}"/>
            @break
        @default
            <x-far-circle class="h-5 w-5" style="color: {{ $status->color }}"/>
            @break
    @endswitch
    <span>{{ $status->name }}</span>
</div>
