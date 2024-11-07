<?php

namespace App\Livewire;

use App\Concerns\CanProcessDescription;
use App\Concerns\CanShowNotification;
use App\Concerns\HasDeletableTaskAction;
use App\Concerns\InteractsWithTaskForm;
use App\Jobs\SendEmailJob;
use App\Mail\NewTaskMail;
use App\Models\Group;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class TasksGroup extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTaskForm;
    use CanProcessDescription;
    use CanShowNotification;
    use HasDeletableTaskAction;

    public Group $group;
    public $tasks;

    public $sortBy;
    public $search;

    protected function getListeners()
    {
        return [
            'refreshGroup:' . $this->group->id => 'refreshGroup',
        ];
    }

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->tasks = $group->tasks;
    }

    public function refreshGroup($tasks, $sortBy)
    {
        $this->sortBy = $sortBy;
        $this->tasks = Task::hydrate($tasks);

        $this->tasks = $this->tasks->map(function ($task) {
            return $task->fresh(['status', 'priority', 'children', 'users', 'comments', 'creator', 'project']);
        });

        if ($this->sortBy === 'priority') {
            $this->tasks = $this->tasks->sortByDesc('priority_id');
        }

        $this->dispatch('refreshedGroup', ['sortBy' => $this->sortBy])->to(TaskRow::class);

        $this->render();
    }

    public function render()
    {
        return view('livewire.tasks-group', [
            'tasks' => $this->tasks,
            'sortBy' => $this->sortBy,
        ]);
    }

    public function createTaskAction(): Action
    {
        return CreateAction::make('createTask')
            ->icon('heroicon-o-plus')
            ->link()
            ->modalWidth('7xl')
            ->modal()
            ->model(Task::class)
            ->label(__('task.add_task'))
            ->form(function () {
                $group_id = $this->group->id;

                return $this->getTaskForm($this->group->project, $group_id);
            })
            ->closeModalByClickingAway(false)
            ->modalCancelAction(fn(StaticAction $action, $data) => $action->action('cancelCreateTask'))
            ->action(function (array $data, CreateAction $action, array $arguments, $form): void {
                $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();

                if (isset($data['description']) && trim($data['description']) != '') {
                    $data['description'] = $this->processDescription($data['description']);
                }
                $task = $this->group->project->tasks()->create(array_merge($data, [
                    'order' => $lastTask ? $lastTask->order + 1 : 0,
                    'created_by' => auth()->id()
                ]));

                $usersToAssign = $data['users'] ?? [];
                $task->users()->sync($usersToAssign);

                $users = $this->group->project->users;
                $author = auth()->user();

                foreach ($users as $user) {
                    if (!$user->hasRole('Client'))
                        SendEmailJob::dispatch(NewTaskMail::class, $user, $task, $author);
                }

                $this->group->fresh('tasks');

                $this->tasks = $this->tasks->push($task);

                $this->showNotification(__('task.task_added'));

                $this->render();

                if ($arguments['another'] ?? false) {
                    $action->callAfter();

                    $action->record(null);

                    $form->model(Task::class);

                    $form->fill();

                    $action->halt();
                }
            });
    }

    public function cancelCreateTask()
    {
        if (Storage::exists('tasks/' . Task::latest()->first()->id + 1)) {
            Storage::deleteDirectory('tasks/' . Task::latest()->first()->id + 1);
        }
    }

    public function editGroupAction(): Action
    {
        return EditAction::make('editGroup')
            ->record($this->group)
            ->modalHeading(__('group.edit_group'))
            ->form([
                TextInput::make('name')
                    ->autofocus()
                    ->label(__('group.name'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->group->update($data);

                $this->showNotification(__('group.group_updated'));
            });
    }

    public function deleteGroupAction(): Action
    {
        return Action::make('deleteGroup')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading(__('group.delete_group') . ' "' . Str::limit($this->group->name, 20) . '" ?')
            ->record($this->group)
            ->action(function (array $arguments): void {
                $this->group->delete();

                $this->showNotification(__('group.group_deleted'));
            });
    }
}
