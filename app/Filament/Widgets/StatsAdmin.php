<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsAdmin extends BaseWidget
{
    public function getNbCreatedTasks()
    {
        $projects = Project::all();
        $tasks = 0;

        foreach ($projects as $project) {
            $tasks += $project->tasks()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        }

        return $tasks;
    }

    public function getNbCompletedTasks()
    {
        $projects = Project::all();
        $tasks = 0;

        foreach ($projects as $project) {
            $tasks += $project->tasks()->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        }

        return $tasks;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Tâches créées', $this->getNbCreatedTasks())
                ->icon('iconoir-task-list')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'title' => 'Nombre de tâches créées cette semaine'
                ]),
            Stat::make('Tâches terminées', $this->getNbCompletedTasks())
                ->icon('grommet-status-good')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'title' => 'Nombre de tâches terminées cette semaine'
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
