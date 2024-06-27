<?php

namespace App\Concerns;

use App\Jobs\SendEmailJob;
use App\Mail\NewTaskMail;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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
            ->form($this->getTaskForm())
            ->action(function (array $data, array $arguments): void {
                $task = Task::find($arguments['task_id']);

                if (isset($data['description']) && trim($data['description']) != '') {
                    $data['description'] = $this->processDescription($data['description']);
                }

                $task->update($data);

                Notification::make()
                    ->success()
                    ->duration(2000)
                    ->title(__('task.task_updated'))
                    ->send();
            });
    }

    public function addSubtaskTooltipAction(): Action
    {
        return CreateAction::make('addSubtaskTooltip')
            ->tooltip(__('task.add_subtask'))
            ->modalWidth('5xl')
            ->modal()
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-plus')
            ->model(Task::class)
            ->modalHeading(__('task.add_subtask'))
            ->form(function (array $arguments) {
                return array_merge($this->getTaskForm($arguments['group_id']), [
                    Hidden::make('project_id')->default($this->record->id),
                ]);
            })
            ->action(function (array $data, array $arguments): void {
                $parentTask = Task::find($arguments['parent_id']);
                $lastTask = $parentTask->children()->orderBy('order', 'desc')->first();

                if (isset($data['description']) && trim($data['description']) != '') {
                    $data['description'] = $this->processDescription($data['description']);
                }

                $task = $parentTask->children()->create(array_merge($data, [
                    'order' => $lastTask ? $lastTask->order + 1 : 0,
                    'created_by' => auth()->id()
                ]));

                $usersToAssign = $data['users'] ?? [];
                $task->users()->sync($usersToAssign);

                $users = $task->project->users;
                $author = auth()->user();

                foreach ($users as $user) {
                    if (!$user->hasRole('Client'))
                        SendEmailJob::dispatch(NewTaskMail::class, $user, $task, $author);
                }

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
