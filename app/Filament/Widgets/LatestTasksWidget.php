<?php

namespace App\Filament\Widgets;

use App\Models\Status;
use Filament\Widgets\Widget;

class LatestTasksWidget extends Widget
{
    protected static string $view = 'filament.widgets.latest-tasks-widget';

    public $tasks = [];

    public function mount()
    {
        $authUser = auth()->user();

        $this->tasks = $authUser->projects()
            ->with(['tasks' => function($query) {
                $query->where('status_id', '!=', Status::whereName('Terminé')->first()->id);
            }])
            ->get()
            ->pluck('tasks')
            ->flatten()
            ->sortByDesc('created_at')
            ->take(10);
    }
}
