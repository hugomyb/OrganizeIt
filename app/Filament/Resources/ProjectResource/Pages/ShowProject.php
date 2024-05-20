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
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\CanAuthorizeResourceAccess;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShowProject extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithActions;
    use InteractsWithTooltipActions;
    use CanAuthorizeResourceAccess;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.show-project';

    public $record;
    public $groups;

    public $statusFilters;
    public $priorityFilters;

    public $toggleCompletedTasks = true;

    public $description;

    public function render(): \Illuminate\Contracts\View\View
    {
        $this->loadGroups();
        return parent::render();
    }

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
                ->modalWidth('7xl')
                ->model(Task::class)
                ->label('Ajouter une tâche')
                ->icon('heroicon-o-plus')
                ->modalHeading('Ajouter une tâche')
                ->form($this->getTaskForm())
                ->modalSubmitActionLabel('Ajouter')
                ->modalCancelAction(fn(StaticAction $action, $data) => $action->action('cancelCreateTask'))
                ->action(function (array $data): void {
                    $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();
                    $this->record->tasks()->create(array_merge($data, [
                        'order' => $lastTask ? $lastTask->order + 1 : 0,
                        'created_by' => auth()->id()
                    ]));

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
        $statusOptions = Status::all()->mapWithKeys(function ($status) {
            $iconHtml = view('components.status-icon', ['status' => $status])->render();
            return [$status->id => $iconHtml];
        })->toArray();

        $priorityOptions = Priority::all()->mapWithKeys(function ($priority) {
            $iconHtml = view('components.priority-icon', ['priority' => $priority])->render();
            return [$priority->id => $iconHtml];
        })->toArray();

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
                ->fileAttachmentsDirectory(fn($record) => $record ? 'tasks/' . $record->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                ->label('Description'),

            Select::make('status_id')
                ->label('Statut')
                ->default(Status::whereName('À faire')->first()->id)
                ->preload()
                ->searchable()
                ->options($statusOptions)
                ->allowHtml()
                ->createOptionForm([
                    TextInput::make('name')
                        ->unique('statuses', ignoreRecord: true)
                        ->label('Nom')
                        ->required(),

                    ColorPicker::make('color')
                        ->label('Couleur')
                ])->createOptionUsing(fn(array $data) => Status::create($data)->getKey())
                ->required(),

            Select::make('priority_id')
                ->label('Priorité')
                ->preload()
                ->searchable()
                ->default(Priority::whereName('Aucune')->first()->id)
                ->options($priorityOptions)
                ->allowHtml()
                ->required(),

            FileUpload::make('attachments')
                ->columnSpanFull()
                ->multiple()
                ->previewable()
                ->downloadable()
                ->multiple()
                ->appendFiles()
                ->preserveFilenames()
                ->visibility('private')
                ->openable()
                ->directory(fn($record) => $record ? 'tasks/' . $record->id . '/attachments' : 'tasks/' . Task::latest()->first()->id + 1 . '/attachments')
                ->label('Pièces jointes')
        ];
    }

    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->icon('heroicon-o-plus')
            ->link()
            ->modalWidth('7xl')
            ->label('Ajouter une tâche')
            ->form(function ($livewire, array $arguments) {
                $group_id = $arguments['group_id'];

                return $this->getTaskForm($group_id);
            })
            ->modalCancelAction(fn(StaticAction $action, $data) => $action->action('cancelCreateTask'))
            ->action(function (array $data): void {
                $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();
                $this->record->tasks()->create(array_merge($data, [
                    'order' => $lastTask ? $lastTask->order + 1 : 0,
                    'created_by' => auth()->id()
                ]));

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
            ->modalWidth('6xl')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->modalContent(fn($record) => view('filament.resources.project-resource.widgets.view-task', ['task' => $record]));
    }

    public function editTaskAction(): Action
    {
        return EditAction::make('editTask')
            ->modalWidth('7xl')
            ->label('Éditer')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm());
    }

    public function setTaskStatus($taskId, $statusId)
    {
        $task = Task::find($taskId);

        $task->update(['status_id' => $statusId]);

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
    }

    public function addUserToProject($userId)
    {
        $this->record->users()->attach($userId);

        Notification::make()
            ->success()
            ->title('Utilisateur ajouté')
            ->body('L\'utilisateur a été ajouté au projet avec succès.')
            ->send();
    }

    public static function canAccess(array $parameters = []): bool
    {
        $record = request()->route()->parameter('record');

        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return Project::find($record)->first()->users->contains(auth()->user());
    }

    public function saveTaskTitle($taskId, $title)
    {
        $task = Task::find($taskId);

        if ($title !== $task->title) {
            $task->title = $title;
            $task->save();

            Notification::make()
                ->success()
                ->title('Titre modifié')
                ->duration(1500)
                ->send();
        }
    }

    public function showNotification($title)
    {
        Notification::make()
            ->success()
            ->title($title)
            ->duration(2000)
            ->send();
    }

    public function fillRichEditorField($task)
    {
        $this->richEditorFieldForm->fill([
            'description' => $task['description']
        ]);
    }

    public function richEditorFieldForm(Form $form): Form
    {
        return $form
            ->live()
            ->extraAttributes([
                'class' => 'w-full'
            ])
            ->schema([
                RichEditor::make('description')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory(fn($record) => $record ? 'tasks/' . $record->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                    ->label('Description'),
            ]);
    }

    public function saveRichEditorDescription($task)
    {
        $richData = $this->richEditorFieldForm->getState();

        $task = Task::find($task['id']);

        $task->update([
            'description' => $richData['description']
        ]);

        $this->showNotification('Description modifiée');
    }

    protected function getForms(): array
    {
        return [
            'richEditorFieldForm'
        ];
    }

    public function openImageAction(): Action
    {
        return Action::make('openImage')
            ->icon('heroicon-o-eye')
            ->label('Voir')
            ->modal()
            ->modalWidth('5xl')
            ->modalContent(fn($arguments) => view('filament.resources.project-resource.widgets.view-image', ['image' => $arguments['image']]));
    }

    public function deleteAttachment($taskId, $attachment)
    {
        $task = Task::find($taskId);
        $previousAttachments = $task->attachments;

        $attachments = collect($previousAttachments)->filter(function ($previousAttachments) use ($attachment) {
            return $previousAttachments !== $attachment;
        })->values();

        $task->update([
            'attachments' => $attachments
        ]);

        $this->showNotification('Pièce jointe supprimée');
    }
}
