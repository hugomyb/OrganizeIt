<?php

namespace App\Concerns;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;

trait InteractsWithTooltipActions
{
    public function editTaskTooltipAction(): Action
    {
        return EditAction::make('editTaskTooltip')
            ->tooltip('Éditer')
            ->modalWidth('5xl')
            ->modal()
            ->modalHeading('Modifier la tâche')
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-pencil')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm());
    }

    public function addSubtaskTooltipAction(): Action
    {
        return Action::make('addSubtaskTooltip')
            ->tooltip('Ajouter sous-tâche')
            ->modalWidth('5xl')
            ->modal()
            ->iconButton()
            ->iconSize(IconSize::Small)
            ->icon('heroicon-o-plus')
            ->modalHeading('Ajouter une sous-tâche')
            ->form(function (array $arguments) {
                return array_merge($this->getTaskForm($arguments['group_id']), [
                    Hidden::make('project_id')->default($this->record->id),
                ]);
            })
            ->action(function (array $data, array $arguments): void {
                $parentTask = Task::find($arguments['parent_id']);
                $lastTask = $parentTask->children()->orderBy('order', 'desc')->first();

                $parentTask->children()->create(array_merge($data, ['order' => $lastTask ? $lastTask->order + 1 : 0]));

                Notification::make()
                    ->success()
                    ->title('Tâche ajoutée')
                    ->body('La tâche a été ajoutée avec succès.')
                    ->send();
            });
    }
}
