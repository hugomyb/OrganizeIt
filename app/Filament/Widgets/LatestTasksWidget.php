<?php

namespace App\Filament\Widgets;

use App\Models\Status;
use App\Models\Task;
use Filament\Widgets\Widget;

class LatestTasksWidget extends Widget
{
    protected static string $view = 'filament.widgets.latest-tasks-widget';

    protected static ?int $sort = 2;

    public $tasks = [];

    public function mount()
    {
        $authUser = auth()->user();

        $projectIds = $authUser->projects()->pluck('projects.id');

        $this->tasks = Task::whereIn('project_id', $projectIds)
            ->where('status_id', '!=', Status::getCompletedStatusId())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->with(['project', 'status', 'priority', 'creator'])
            ->get();
    }
}
