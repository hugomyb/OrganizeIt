@switch($status->name)
    @case('À faire')
        <x-pepicon-hourglass-circle class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
        @break
    @case('En cours')
        <x-carbon-in-progress class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
        @break
    @case('Terminé')
        <x-grommet-status-good class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
        @break
    @default
        <x-pepicon-hourglass-circle class="h-5 w-5 mx-1" style="color: {{ $status->color }}"/>
@endswitch
