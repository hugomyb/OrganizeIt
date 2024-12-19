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

    public $defaultAction;

    public $defaultActionArguments;

    public Group $group;
    public $tasks = [];
    public $visibleTaskCount = 10;
    public $totalTaskCount;

    public $statusFilters;
    public $priorityFilters;

    public $sortBy;
    public $search;

    protected function getListeners()
    {
        return [
            'refreshGroup:' . $this->group->id => 'refreshGroup',
            'loadTaskById' => 'loadTaskById',
            'reloadTasks:' . $this->group->id => 'reloadTasks',
        ];
    }

    public function mount(Group $group, $statusFilters, $priorityFilters, $search, $sortBy)
    {
        $this->group = $group;
        $this->statusFilters = $statusFilters;
        $this->priorityFilters = $priorityFilters;
        $this->search = $search;
        $this->sortBy = $sortBy;

        $this->loadVisibleTasks();
    }

    private function getFilteredTasksQuery()
    {
        $statusFilterIds = collect($this->statusFilters)->pluck('id')->filter()->all();
        $priorityFilterIds = collect($this->priorityFilters)->pluck('id')->filter()->all();
        $searchTerm = $this->search ?? '';

        // Étape 1 : Récupérer les IDs des tâches enfants filtrées
        $filteredChildTasks = $this->group->tasks()
            ->where(function ($query) use ($statusFilterIds, $priorityFilterIds, $searchTerm) {
                if (!empty($statusFilterIds)) {
                    $query->whereIn('status_id', $statusFilterIds);
                }
                if (!empty($priorityFilterIds)) {
                    $query->whereIn('priority_id', $priorityFilterIds);
                }
                if (!empty($searchTerm)) {
                    $query->where('title', 'like', '%' . $searchTerm . '%');
                }
            })->get();

        $filteredChildIds = $filteredChildTasks->pluck('id')->toArray();

        // Étape 2 : Récupérer récursivement les parents jusqu'à la racine
        $parentIds = $this->getParentTaskIds($filteredChildIds);

        // Étape 3 : Charger les tâches parentes et enfants jusqu'à 4 niveaux de profondeur
        return $this->group->tasks()
            ->whereIn('id', array_merge($parentIds, $filteredChildIds))
            ->whereNull('parent_id') // Récupérer les tâches racines
            ->with([
                'children' => function ($query) use ($filteredChildIds) {
                    $query->whereIn('id', $filteredChildIds)
                        ->with(['children' => function ($query) use ($filteredChildIds) {
                            // Deuxième niveau
                            $query->whereIn('id', $filteredChildIds)
                                ->with(['children' => function ($query) use ($filteredChildIds) {
                                    // Troisième niveau
                                    $query->whereIn('id', $filteredChildIds);
                                }]);
                        }]);
                }
            ])
            ->orderBy($this->sortBy === 'priority' ? 'priority_id' : 'order', $this->sortBy === 'priority' ? 'desc' : 'asc');
    }

    private function getParentTaskIds(array $childIds)
    {
        $parentIds = [];

        while (!empty($childIds)) {
            $parents = $this->group->tasks()
                ->whereIn('id', $childIds)
                ->pluck('parent_id')->filter()->all();

            $parentIds = array_merge($parentIds, $childIds);
            $childIds = $parents;
        }

        return array_unique($parentIds);
    }

    public function loadTaskById($taskId)
    {
        if (!collect($this->tasks)->pluck('id')->contains($taskId)) {
            $task = Task::find($taskId);

            if ($task) {
                $task->loadMissing(['project', 'priority', 'status', 'children', 'comments', 'users', 'creator']);
                $this->tasks->push($task);
            }
        }
    }

    public function loadVisibleTasks(): void
    {
        $tasksQuery = $this->getFilteredTasksQuery();

        $loadedTasks = collect();
        $remainingLimit = $this->visibleTaskCount;
        $processedTaskIds = [];

        $tasksGroupedByParent = $tasksQuery
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        foreach ($tasksGroupedByParent as $task) {
            $this->addTaskWithChildren($task, $loadedTasks, $remainingLimit, $processedTaskIds);

            if ($remainingLimit <= 0) {
                break;
            }
        }

        $this->tasks = $loadedTasks->whereNull('parent_id');
        $this->totalTaskCount = $tasksQuery->count();
        $this->render();
    }

    private function addTaskWithChildren($task, &$loadedTasks, &$remainingLimit, &$processedTaskIds)
    {
        if ($remainingLimit <= 0 || in_array($task->id, $processedTaskIds)) {
            return;
        }

        $loadedTasks->push($task);
        $processedTaskIds[] = $task->id;
        $remainingLimit--;

        $children = $task->children;

        foreach ($children as $child) {
            $this->addTaskWithChildren($child, $loadedTasks, $remainingLimit, $processedTaskIds);

            if ($remainingLimit <= 0) {
                break;
            }
        }
    }

    public function reloadTasks($statusFilters, $priorityFilters, $search, $sortBy): void
    {
        $this->statusFilters = $statusFilters;
        $this->priorityFilters = $priorityFilters;
        $this->search = $search;
        $this->sortBy = $sortBy;

        $this->visibleTaskCount = 10;

        $this->loadVisibleTasks();
    }

    public function loadMoreTasks(): void
    {
        $tasksQuery = $this->getFilteredTasksQuery();

        $newTasks = $tasksQuery
            ->with('project')
            ->skip($this->visibleTaskCount)
            ->take(10)
            ->get();

        $this->tasks = $this->tasks->merge($newTasks);

        $this->visibleTaskCount += $newTasks->count();
    }

    public function refreshGroup($tasks, $sortBy): void
    {
        $this->sortBy = $sortBy;

        $hydratedTasks = Task::hydrate($tasks);

        $existingTaskIds = $this->tasks->pluck('id');
        $newTasks = $hydratedTasks->filter(fn($task) => !$existingTaskIds->contains($task->id));

        $this->tasks = $this->tasks->merge($newTasks)->values();

        $this->totalTaskCount = $hydratedTasks->count();

        $this->render();
    }

    public function render()
    {
        return view('livewire.tasks-group', [
            'tasks' => $this->tasks,
            'hasMoreTasks' => $this->visibleTaskCount < $this->totalTaskCount,
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
