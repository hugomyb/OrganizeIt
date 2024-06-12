<?php

namespace App\Filament\Resources\ProjectResource\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;

class RecentProjects extends Widget
{
    protected static string $view = 'filament.resources.project-resource.widgets.recent-projects';

    protected static ?int $sort = 1;

    public $projects = [];

    public function mount()
    {
        $authUser = auth()->user();

        if ($authUser->hasRole('Admin')) {
            $this->projects = Project::orderByDesc('created_at')->get();
        } else {
            $this->projects = $authUser->projects()->orderByDesc('created_at')->get();
        }
    }
}
