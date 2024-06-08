<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class LatestTasksWidget extends Widget
{
    protected static string $view = 'filament.widgets.latest-tasks-widget';

    public $tasks = [];

    public function mount()
    {
        $authUser = auth()->user();

        $this->tasks = $authUser->tasks()->latest()->limit(10)->get();
    }
}
