<?php

namespace App\Livewire;

use App\Concerns\CanManageTasksInfo;
use App\Concerns\CanProcessDescription;
use App\Concerns\CanShowNotification;
use App\Concerns\InteractsWithTaskForm;
use App\Jobs\SendEmailJob;
use App\Mail\NewTaskMail;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskRow extends Component implements HasForms, HasActions
{
    use InteractsWithTaskForm;
    use InteractsWithActions;
    use InteractsWithForms;
    use CanProcessDescription;
    use CanShowNotification;
    use CanManageTasksInfo;

    public Task $task;
    public $sortBy;

    public function mount(Task $task)
    {
        $this->task = $task;
    }

    public function render()
    {
        $sortedChildren = $this->task->children;

        if ($this->sortBy === 'priority') {
            $sortedChildren = $sortedChildren->sortByDesc('priority_id');
        }

        return view('livewire.task-row', [
            'task' => $this->task,
            'sortedChildren' => $sortedChildren
        ]);
    }

    protected function getListeners()
    {
        return [
            'modal-closed:' . $this->task->id => 'refreshTask'
        ];
    }

    public function refreshTask()
    {
        if ($this->task->parent_id) {
            $this->dispatch('modal-closed:' . $this->task->parent_id);
        }

        $this->render();
    }

    public function editTaskTooltipAction(): Action
    {
        return EditAction::make('editTaskTooltip')
            ->tooltip(__('task.edit'))
            ->modalWidth('5xl')
            ->modal()
            ->modalHeading(__('task.edit_task'))
            ->iconButton()
            ->closeModalByClickingAway(false)
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-pencil')
            ->record($this->task)
            ->form($this->getTaskForm($this->task->project))
            ->action(function (array $data): void {
                $task = $this->task;

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
            ->closeModalByClickingAway(false)
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-plus')
            ->model(Task::class)
            ->modalHeading(__('task.add_subtask'))
            ->form(function () {
                return array_merge($this->getTaskForm($this->task->project, $this->task->group_id), [
                    Hidden::make('project_id')->default($this->task->project->id),
                ]);
            })
            ->action(function (array $data): void {
                $parentTask = $this->task;
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
            ->modal()
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading(fn() => __('task.delete_task') . ' "' . Str::limit($this->task->title, 20) . '" ?')
            ->modalDescription(fn() => $this->task->children()->count()
                ? __('task.delete_description') . $this->task->children()->count() . __('task.delete_description_subtasks')
                : __('task.confirm_delete'))
            ->record($this->task)
            ->action(function (): void {
                $this->task->delete();

                Notification::make()
                    ->success()
                    ->duration(2000)
                    ->title(__('task.task_deleted'))
                    ->send();
            });
    }
}
