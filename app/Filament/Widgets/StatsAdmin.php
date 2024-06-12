<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsAdmin extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    protected function getPeriodDates($period)
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'yesterday':
                return [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()];
            case 'last_7_days':
                return [now()->subDays(6)->startOfDay(), now()->endOfDay()];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'last_week':
                return [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()];
            case 'last_30_days':
                return [now()->subDays(29)->startOfDay(), now()->endOfDay()];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'last_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            case 'last_year':
                return [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()];
            default:
                return [now()->startOfWeek(), now()->endOfWeek()];
        }
    }

    protected function getNbTasksByDay($type, $startDate, $endDate)
    {
        $projects = Project::all();
        $tasksByDay = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $tasks = 0;
            foreach ($projects as $project) {
                $tasks += $project->tasks()
                    ->whereDate($type, $currentDate)
                    ->count();
            }
            $tasksByDay[] = $tasks;
            $currentDate->addDay();
        }

        return $tasksByDay;
    }

    protected function getNbCreatedTasks($startDate, $endDate)
    {
        return array_sum($this->getNbTasksByDay('created_at', $startDate, $endDate));
    }

    protected function getNbCompletedTasks($startDate, $endDate)
    {
        return array_sum($this->getNbTasksByDay('completed_at', $startDate, $endDate));
    }

    protected function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function getPreviousPeriodDates($period)
    {
        switch ($period) {
            case 'today':
                return [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()];
            case 'yesterday':
                return [now()->subDays(2)->startOfDay(), now()->subDays(2)->endOfDay()];
            case 'last_7_days':
                return [now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay()];
            case 'this_week':
                return [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()];
            case 'last_week':
                return [now()->subWeeks(2)->startOfWeek(), now()->subWeeks(2)->endOfWeek()];
            case 'last_30_days':
                return [now()->subDays(59)->startOfDay(), now()->subDays(30)->endOfDay()];
            case 'this_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'last_month':
                return [now()->subMonths(2)->startOfMonth(), now()->subMonths(2)->endOfMonth()];
            case 'this_year':
                return [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()];
            case 'last_year':
                return [now()->subYears(2)->startOfYear(), now()->subYears(2)->endOfYear()];
            default:
                return [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()];
        }
    }

    protected function getStats(): array
    {
        $period = $this->filters['period'] ?? 'this_week';
        [$startDate, $endDate] = $this->getPeriodDates($period);
        [$prevStartDate, $prevEndDate] = $this->getPreviousPeriodDates($period);

        $currentCreatedTasks = $this->getNbCreatedTasks($startDate, $endDate);
        $previousCreatedTasks = $this->getNbCreatedTasks($prevStartDate, $prevEndDate);
        $createdTasksChange = $this->calculateChange($currentCreatedTasks, $previousCreatedTasks);

        $currentCompletedTasks = $this->getNbCompletedTasks($startDate, $endDate);
        $previousCompletedTasks = $this->getNbCompletedTasks($prevStartDate, $prevEndDate);
        $completedTasksChange = $this->calculateChange($currentCompletedTasks, $previousCompletedTasks);

        $createdTasksByDay = $this->getNbTasksByDay('created_at', $startDate, $endDate);
        $completedTasksByDay = $this->getNbTasksByDay('completed_at', $startDate, $endDate);

        return [
            Stat::make('Tâches créées', $currentCreatedTasks)
                ->icon('iconoir-task-list')
                ->description(($createdTasksChange >= 0 ? '+' : '') . number_format($createdTasksChange, 2) . '%')
                ->descriptionIcon($createdTasksChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($createdTasksByDay)
                ->color($createdTasksChange >= 0 ? 'success' : 'danger'),

            Stat::make('Tâches terminées', $currentCompletedTasks)
                ->icon('grommet-status-good')
                ->description(($completedTasksChange >= 0 ? '+' : '') . number_format($completedTasksChange, 2) . '%')
                ->descriptionIcon($completedTasksChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($completedTasksByDay)
                ->color($completedTasksChange >= 0 ? 'success' : 'danger'),
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
