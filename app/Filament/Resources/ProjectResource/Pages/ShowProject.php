<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Widgets\TasksTree;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ShowProject extends Page
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.show-project';

    public $record;

    public function mount($record)
    {
        $this->record = Project::find($record);

        foreach ($this->record->groups as $group) {
            $this->reorderTasksInGroup($group->id);
            foreach ($group->tasks as $task) {
                $this->reorderSubTasks($task);
            }
        }
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
                ->required(),

            RichEditor::make('description')
                ->columnSpanFull()
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
            ->form(function (array $arguments) {
                $group_id = $arguments['group_id'];

                return $this->getTaskForm($group_id);
            })
            ->action(function (array $data): void {
                $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();
                $this->record->tasks()->create(array_merge($data, ['order' => $lastTask ? $lastTask->order + 1 : 0]));

                Notification::make()
                    ->success()
                    ->title('Tâche ajoutée')
                    ->body('La tâche a été ajoutée avec succès.')
                    ->send();
            });
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
            ->form($this->getTaskForm())
            ->after(function () {
                $this->dispatch('close-modal', 'viewTask');
            });
    }

    public function setTaskStatus($taskId, $statusId)
    {
        $task = Task::find($taskId);

        $task->update(['status_id' => $statusId]);
    }

    public function setTaskPriority($taskId, $priorityId)
    {
        $task = Task::find($taskId);

        $task->update(['priority_id' => $priorityId]);
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

    
}
