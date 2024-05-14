<?php

namespace App\Filament\Resources\ProjectResource\Widgets;

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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class TasksTree extends Widget implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.tasks-tree';

    protected int|string|array $columnSpan = 2;

    public Project $record;

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
            });
    }

    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->icon('heroicon-o-plus')
            ->link()
            ->label('Ajouter une tâche')
            ->form(function (array $arguments) {
                $group_id = $arguments['group_id'];

                return [
                    Select::make('group_id')
                        ->label('Groupe')
                        ->required()
                        ->default($group_id)
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
                        ->options(Status::pluck('name', 'id'))
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nom')
                                ->required(),
                        ])
                        ->required(),

                    Select::make('priority_id')
                        ->label('Priorité')
                        ->options(Priority::pluck('name', 'id'))
                        ->required(),

                    FileUpload::make('attachments')
                        ->columnSpanFull()
                        ->multiple()
                        ->label('Pièces jointes')
                ];
            })
            ->action(function (array $data): void {
                $this->record->tasks()->create($data);
            });
    }

    public function editTaskAction(): Action
    {
        return ViewAction::make('editTask')
            ->slideOver()
            ->modalWidth('5xl')
            ->record(fn (array $arguments) => Task::find($arguments['task_id']))
            ->form([
                Select::make('group_id')
                    ->label('Groupe')
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
                    ->options(Status::pluck('name', 'id'))
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                    ])
                    ->required(),

                Select::make('priority_id')
                    ->label('Priorité')
                    ->options(Priority::pluck('name', 'id'))
                    ->required(),

                FileUpload::make('attachments')
                    ->columnSpanFull()
                    ->multiple()
                    ->label('Pièces jointes')
            ]);
    }
}
