<?php

namespace App\Concerns;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Str;

trait InteractsWithTooltipActions
{
    public function editTaskTooltipAction(): Action
    {
        return EditAction::make('editTaskTooltip')
            ->tooltip(__('task.edit'))
            ->modalWidth('5xl')
            ->modal()
            ->modalHeading(__('task.edit_task'))
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-pencil')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm());
    }

    public function addSubtaskTooltipAction(): Action
    {
        return Action::make('addSubtaskTooltip')
            ->tooltip(__('task.add_subtask'))
            ->modalWidth('5xl')
            ->modal()
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-plus')
            ->modalHeading(__('task.add_subtask'))
            ->form(function (array $arguments) {
                return array_merge($this->getTaskForm($arguments['group_id']), [
                    Hidden::make('project_id')->default($this->record->id),
                ]);
            })
            ->action(function (array $data, array $arguments): void {
                $parentTask = Task::find($arguments['parent_id']);
                $lastTask = $parentTask->children()->orderBy('order', 'desc')->first();

                $parentTask->children()->create(array_merge($data, [
                    'order' => $lastTask ? $lastTask->order + 1 : 0,
                    'created_by' => auth()->id()
                ]));

                Notification::make()
                    ->success()
                    ->duration(2000)
                    ->title(__('task.subtask_added'))
                    ->send();
            });
    }

    public function deleteTaskTooltipAction(): Action
    {
        return Action::make('deleteTaskTooltip')
            ->tooltip(__('task.delete'))
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading(fn(array $arguments) => __('task.delete_task') . ' "' . Str::limit(Task::find($arguments['task_id'])->title, 20) . '" ?')
            ->modalDescription(fn(array $arguments) => Task::find($arguments['task_id'])->children()->count()
                ? __('task.delete_description') . Task::find($arguments['task_id'])->children()->count() . __('task.delete_description_subtasks')
                : __('task.confirm_delete'))
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->action(function (array $arguments): void {
                Task::find($arguments['task_id'])->delete();

                Notification::make()
                    ->success()
                    ->duration(2000)
                    ->title(__('task.task_deleted'))
                    ->send();
            });
    }
}
