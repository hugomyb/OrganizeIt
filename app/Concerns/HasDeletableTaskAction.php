<?php

namespace App\Concerns;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Str;

trait HasDeletableTaskAction
{
    public function mountDeleteTaskAction($taskId)
    {
        $this->mountAction('deleteTaskTooltip', ['task_id' => $taskId]);
    }

    public function deleteTaskTooltipAction(): Action
    {
        return Action::make('deleteTaskTooltip')
            ->tooltip(__('task.delete'))
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->color('danger')
            ->modal()
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading(function (array $arguments) {
                $task = Task::find($arguments['task_id']);
                return __('task.delete_task') . ' "' . Str::limit($task->title, 20) . '" ?';
            })
            ->modalDescription(function (array $arguments) {
                $task = Task::find($arguments['task_id']);

                return $task->children()->count()
                    ? __('task.delete_description') . $task->children()->count() . __('task.delete_description_subtasks')
                    : __('task.confirm_delete');
            })
            ->action(function (array $arguments, Action $action): void {
                $task = Task::find($arguments['task_id']);

                $task->delete();

                $action->success();
            })->successNotification(
                Notification::make()
                    ->success()
                    ->duration(2000)
                    ->title(__('task.task_deleted'))
            );
    }
}
