<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Concerns\InteractsWithTooltipActions;
use App\Filament\Resources\ProjectResource;
use App\Models\Group;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\EditAction;
use Filament\Actions\StaticAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShowProject extends Page
{
    use InteractsWithActions;
    use InteractsWithTooltipActions;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.show-project';

    public $record;
    public $groups;

    public $statusFilters;
    public $priorityFilters;

    public $toggleCompletedTasks = true;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::ScreenTwoExtraLarge;
    }

    public function mount($record)
    {
        $this->record = Project::find($record);
        $this->statusFilters = collect();
        $this->priorityFilters = collect();

        $this->toggleShowCompletedTasks();
    }

    public function loadGroups()
    {
        $statusIds = $this->statusFilters->pluck('id')->toArray();
        $priorityIds = $this->priorityFilters->pluck('id')->toArray();

        $this->groups = Group::where('project_id', $this->record->id)
            ->with(['tasks' => function ($query) use ($statusIds, $priorityIds) {
                $query->where(function ($query) use ($statusIds, $priorityIds) {
                    if (!empty($statusIds)) {
                        $query->whereIn('status_id', $statusIds);
                    }
                    if (!empty($priorityIds)) {
                        $query->whereIn('priority_id', $priorityIds);
                    }
                })
                    ->orWhereHas('children', function ($query) use ($statusIds, $priorityIds) {
                        $query->where(function ($query) use ($statusIds, $priorityIds) {
                            if (!empty($statusIds)) {
                                $query->whereIn('status_id', $statusIds);
                            }
                            if (!empty($priorityIds)) {
                                $query->whereIn('priority_id', $priorityIds);
                            }
                        });
                    });

                $query->with(['children' => function ($query) use ($statusIds, $priorityIds) {
                    $this->applyRecursiveFilters($query, $statusIds, $priorityIds);
                }, 'parent']);
            }])
            ->get();
    }

    protected function applyRecursiveFilters($query, $statusIds, $priorityIds)
    {
        $query->where(function ($query) use ($statusIds, $priorityIds) {
            if (!empty($statusIds)) {
                $query->whereIn('status_id', $statusIds);
            }
            if (!empty($priorityIds)) {
                $query->whereIn('priority_id', $priorityIds);
            }
        })
            ->orWhereHas('children', function ($query) use ($statusIds, $priorityIds) {
                $query->where(function ($query) use ($statusIds, $priorityIds) {
                    if (!empty($statusIds)) {
                        $query->whereIn('status_id', $statusIds);
                    }
                    if (!empty($priorityIds)) {
                        $query->whereIn('priority_id', $priorityIds);
                    }
                });

                // Appel récursif pour les enfants
                $query->with(['children' => function ($query) use ($statusIds, $priorityIds) {
                    $this->applyRecursiveFilters($query, $statusIds, $priorityIds);
                }]);
            });
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('headerCreateTask')
                ->model(Task::class)
                ->label('Ajouter une tâche')
                ->icon('heroicon-o-plus')
                ->modalHeading('Ajouter une tâche')
                ->form($this->getTaskForm())
                ->modalSubmitActionLabel('Ajouter')
                ->action(function (array $data): void {
                    $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();
                    $this->record->tasks()->create(array_merge($data, ['order' => $lastTask ? $lastTask->order + 1 : 0]));

                    Notification::make()
                        ->success()
                        ->title('Tâche ajoutée')
                        ->body('La tâche a été ajoutée avec succès.')
                        ->send();
                }),
        ];
    }

    public function createGroupAction(): Action
    {
        return Action::make('createGroup')
            ->outlined()
            ->label('Créer un groupe')
            ->form([
                TextInput::make('name')
                    ->autofocus()
                    ->label('Nom')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->record->groups()->create($data);

                Notification::make()
                    ->success()
                    ->title('Groupe créé')
                    ->body('Le groupe a été créé avec succès.')
                    ->send();
            });
    }

    public function editGroupAction(): Action
    {
        return EditAction::make('editGroup')
            ->record(fn(array $arguments) => Group::find($arguments['group_id']))
            ->modalHeading('Éditer le groupe')
            ->form([
                TextInput::make('name')
                    ->autofocus()
                    ->label('Nom')
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                Group::find($arguments['group_id'])->update($data);

                Notification::make()
                    ->success()
                    ->title('Groupe modifié')
                    ->body('Le groupe a été modifié avec succès.')
                    ->send();
            });
    }

    public function deleteGroupAction(): Action
    {
        return Action::make('deleteGroup')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading(fn(array $arguments) => 'Supprimer le groupe "' . Str::limit(Group::find($arguments['group_id'])->name, 20) . '" ?')
            ->record(fn(array $arguments) => Group::find($arguments['group_id']))
            ->action(function (array $arguments): void {
                Group::find($arguments['group_id'])->delete();

                Notification::make()
                    ->success()
                    ->title('Groupe supprimé')
                    ->body('Le groupe a été supprimé avec succès.')
                    ->send();
            });
    }

    public function getTaskForm($groupId = null): array
    {
        return [
            Select::make('group_id')
                ->preload()
                ->searchable()
                ->label('Groupe')
                ->default($groupId ?? $this->record->groups->first()->id)
                ->required()
                ->options($this->record->groups->pluck('name', 'id')),

            TextInput::make('title')
                ->autofocus()
                ->label('Titre')
                ->columnSpanFull()
                ->live()
                ->required(),

            RichEditor::make('description')
                ->columnSpanFull()
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory(fn ($record) => $record ? 'tasks/' . $record->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                ->label('Description'),

            Select::make('status_id')
                ->label('Statut')
                ->default(Status::whereName('À faire')->first()->id)
                ->preload()
                ->searchable()
                ->options(Status::pluck('name', 'id'))
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Nom')
                        ->required(),
                ])
                ->required(),

            Select::make('priority_id')
                ->label('Priorité')
                ->preload()
                ->searchable()
                ->default(Priority::whereName('Aucune')->first()->id)
                ->options(Priority::pluck('name', 'id'))
                ->required(),

            FileUpload::make('attachments')
                ->columnSpanFull()
                ->multiple()
                ->label('Pièces jointes')
        ];
    }

    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->icon('heroicon-o-plus')
            ->link()
            ->label('Ajouter une tâche')
            ->form(function ($livewire, array $arguments) {
                $group_id = $arguments['group_id'];

                return $this->getTaskForm($group_id);
            })
            ->modalCancelAction(fn (StaticAction $action, $data) => $action->action('cancelCreateTask'))
            ->action(function (array $data): void {
                $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();
                $task = $this->record->tasks()->create(array_merge($data, ['order' => $lastTask ? $lastTask->order + 1 : 0]));

                Notification::make()
                    ->success()
                    ->title('Tâche ajoutée')
                    ->body('La tâche a été ajoutée avec succès.')
                    ->send();
            });
    }

    public function cancelCreateTask()
    {
        if (Storage::exists('tasks/' . Task::latest()->first()->id + 1)) {
            Storage::deleteDirectory('tasks/' . Task::latest()->first()->id + 1);
        }
    }

    public function viewTaskAction(): Action
    {
        return ViewAction::make('viewTask')
            ->modalHeading('')
            ->slideOver()
            ->modalWidth('5xl')
            ->extraModalFooterActions([
                $this->editTaskAction()
            ])
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm());
    }

    public function editTaskAction(): Action
    {
        return EditAction::make('editTask')
            ->modalWidth('5xl')
            ->label('Éditer')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm());
    }

    public function setTaskStatus($taskId, $statusId)
    {
        $task = Task::find($taskId);

        $task->update(['status_id' => $statusId]);

        $this->loadGroups();

        Notification::make()
            ->success()
            ->title('Statut modifiée')
            ->body('Le statut de la tâche a été modifiée avec succès.')
            ->send();
    }

    public function setTaskPriority($taskId, $priorityId)
    {
        $task = Task::find($taskId);

        $task->update(['priority_id' => $priorityId]);

        $this->loadGroups();

        Notification::make()
            ->success()
            ->title('Priorité modifiée')
            ->body('La priorité de la tâche a été modifiée avec succès.')
            ->send();
    }

    public function updateTaskOrder($groupId, $nestableJson)
    {
        $taskData = json_decode($nestableJson, true);
        $taskData = array_filter($taskData, function ($task) {
            return $task['id'] !== 'placeholder';
        });
        $this->updateOrder($taskData, $groupId);
    }

    private function updateOrder($tasks, $groupId)
    {
        foreach ($tasks as $index => $task) {
            $this->updateTask($task, $groupId, null, $index);
        }

        $this->loadGroups();
    }

    private function updateTask($task, $groupId, $parentId, $order)
    {
        $taskModel = Task::find($task['id']);
        $taskModel->order = $order;
        $taskModel->group_id = $groupId;
        $taskModel->parent_id = $parentId;
        $taskModel->save();

        if (isset($task['children'])) {
            foreach ($task['children'] as $childIndex => $child) {
                $this->updateTask($child, $groupId, $taskModel->id, $childIndex);
            }
        }
    }

    protected function reorderTasksInGroup($groupId)
    {
        $tasks = Task::where('group_id', $groupId)->whereNull('parent_id')->orderBy('order')->get();
        $order = 0;
        foreach ($tasks as $task) {
            $task->order = $order++;
            $task->save();

            // Réorganiser les sous-tâches
            $this->reorderSubTasks($task);
        }
    }

    protected function reorderSubTasks($task)
    {
        $subTasks = $task->children()->orderBy('order')->get();
        $order = 0;
        foreach ($subTasks as $subTask) {
            $subTask->order = $order++;
            $subTask->save();

            $this->reorderSubTasks($subTask);
        }
    }

    public function assignUserToTask($userId, $taskId)
    {
        $task = Task::find($taskId);
        if ($task) {
            if (!$task->users()->where('user_id', $userId)->exists()) {
                $task->users()->attach($userId);
            }
        }

        $this->loadGroups();

        Notification::make()
            ->success()
            ->title('Utilisateur assigné')
            ->body('L\'utilisateur a été assigné à la tâche avec succès.')
            ->send();
    }

    public function toggleUserToTask($userId, $taskId)
    {
        $task = Task::find($taskId);
        if ($task) {
            if ($task->users()->where('user_id', $userId)->exists()) {
                $task->users()->detach($userId);

                Notification::make()
                    ->success()
                    ->title('Utilisateur retiré')
                    ->body('L\'utilisateur a été retiré de la tâche avec succès.')
                    ->send();
            } else {
                $task->users()->attach($userId);

                Notification::make()
                    ->success()
                    ->title('Utilisateur assigné')
                    ->body('L\'utilisateur a été assigné à la tâche avec succès.')
                    ->send();
            }
        }

        $this->loadGroups();
    }

    public function updateStatusFilter($statusId)
    {
        $status = Status::find($statusId);
        if ($status) {
            if ($this->statusFilters->contains('id', $statusId)) {
                $this->statusFilters = $this->statusFilters->reject(fn($filter) => $filter['id'] === $statusId)->values();
            } else {
                $this->statusFilters->push($status);
            }
            $this->loadGroups();
        }
    }

    public function updatePriorityFilter($priorityId)
    {
        $priority = Priority::find($priorityId);
        if ($priority) {
            if ($this->priorityFilters->contains('id', $priorityId)) {
                $this->priorityFilters = $this->priorityFilters->reject(fn($filter) => $filter['id'] === $priorityId)->values();
            } else {
                $this->priorityFilters->push($priority);
            }
            $this->loadGroups();
        }
    }

    public function toggleShowCompletedTasks()
    {
        $this->toggleCompletedTasks = !$this->toggleCompletedTasks;

        if ($this->toggleCompletedTasks) {
            $statuses = collect();
            $this->statusFilters = $statuses;
        } else {
            $statusesExceptCompleted = Status::where('name', '!=', 'Terminé')->get();
            $this->statusFilters = $statusesExceptCompleted;
        }

        $this->loadGroups();
    }
}
