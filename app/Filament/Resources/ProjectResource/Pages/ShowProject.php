<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Widgets\TasksTree;
use App\Models\Group;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Filament\Actions\Action;
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
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return $this->record->name;
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
                    $this->record->tasks()->create($data);

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
                $this->record->tasks()->create($data);

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

    public function saveTaskOrder($taskId, $newPosition, $toGroupId)
    {
        $task = Task::find($taskId);
        $fromGroupId = $task->group_id;
        $currentPosition = $task->order;

        if ($fromGroupId == $toGroupId) {
            if ($newPosition == $currentPosition) {
                return; // Aucun changement nécessaire si la position n'a pas changé.
            }

            // Détermine si la tâche est déplacée vers le haut ou le bas dans la liste
            $direction = $newPosition > $currentPosition ? 'down' : 'up';

            // Récupère toutes les tâches qui pourraient être affectées par ce changement
            $query = Task::query()
                ->where('group_id', $task->group_id)
                ->where('id', '!=', $taskId);

            if ($direction === 'up') {
                // Déplacer vers le haut: Augmente l'ordre des tâches entre les anciennes et nouvelles positions
                $query->whereBetween('order', [$newPosition, $currentPosition - 1])
                    ->increment('order');
            } else {
                // Déplacer vers le bas: Diminue l'ordre des tâches entre les anciennes et nouvelles positions
                $query->whereBetween('order', [$currentPosition + 1, $newPosition])
                    ->decrement('order');
            }

            // Mise à jour de la position de la tâche déplacée seulement après ajustement des autres tâches
            $task->order = $newPosition;
            $task->save();
        } else {
            // Incrémenter l'ordre des tâches suivantes dans le groupe de destination
            Task::where('group_id', $toGroupId)
                ->where('order', '>=', $newPosition)
                ->increment('order');

            // Mise à jour du groupe et de l'ordre de la tâche déplacée
            $task->group_id = $toGroupId;
            $task->order = $newPosition;
            $task->save();

            // Réorganiser l'ordre des tâches dans l'ancien groupe et le nouveau groupe
            $this->reorderTasksInGroup($fromGroupId);
            $this->reorderTasksInGroup($toGroupId);
        }
    }

    protected function reorderTasksInGroup($groupId)
    {
        $tasks = Task::where('group_id', $groupId)->orderBy('order')->get();
        $order = 0;
        foreach ($tasks as $task) {
            $task->order = $order++;
            $task->save();
        }
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
