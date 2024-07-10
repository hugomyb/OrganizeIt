<?php

namespace App\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\Widget;

class RecentProjects extends Widget
{
    protected static string $view = 'filament.resources.project-resource.widgets.recent-projects';

    protected static ?int $sort = 1;

    public $projects = [];

    public function mount()
    {
        $authUser = auth()->user();

        $this->projects = $authUser->projects()->orderByDesc('created_at')->get()->take(10);
    }
}
