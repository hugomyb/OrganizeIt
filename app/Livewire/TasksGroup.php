<?php

namespace App\Livewire;

use App\Concerns\CanProcessDescription;
use App\Concerns\CanShowNotification;
use App\Concerns\InteractsWithTaskForm;
use App\Jobs\SendEmailJob;
use App\Mail\NewCommitMail;
use App\Mail\NewTaskMail;
use App\Models\Group;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\StaticAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class TasksGroup extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTaskForm;
    use CanProcessDescription;
    use CanShowNotification;

    public Group $group;
    public $tasks;

    public $description;
    public $attachments;

    public $comment;

    public $sortBy;

    public function mount(Group $group)
    {
        $this->group = $group;
    }

    public function render()
    {
        $this->tasks = $this->group->tasks->sortBy('order');
        return view('livewire.tasks-group');
    }

    public function placeholder()
    {
        return <<<'HTML'
            <x-filament::section
                collapsible
                persist-collapsed
                id="group-{{ $group->id }}"
                style="margin-bottom: 30px; width: 100%">

                <x-slot name="heading">
                    <div role="status" class="max-w-sm animate-pulse">
                        <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-48"></div>
                    </div>
                </x-slot>

                <div class="flex justify-center items-center py-4">
                    <x-filament::loading-indicator class="h-6 w-6" />
                </div>
            </x-filament::section>
        HTML;
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
            ->action(function (array $data): void {
                $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();

                if (isset($data['description']) && trim($data['description']) != '') {
                    $data['description'] = $this->processDescription($data['description']);
                }
                $task = $this->record->tasks()->create(array_merge($data, [
                    'order' => $lastTask ? $lastTask->order + 1 : 0,
                    'created_by' => auth()->id()
                ]));

                $usersToAssign = $data['users'] ?? [];
                $task->users()->sync($usersToAssign);

                $users = $this->record->users;
                $author = auth()->user();

                foreach ($users as $user) {
                    if (!$user->hasRole('Client'))
                        SendEmailJob::dispatch(NewTaskMail::class, $user, $task, $author);
                }

                $this->showNotification(__('task.task_added'));
            });
    }

    public function cancelCreateTask()
    {
        if (Storage::exists('tasks/' . Task::latest()->first()->id + 1)) {
            Storage::deleteDirectory('tasks/' . Task::latest()->first()->id + 1);
        }
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
            ->record(fn (array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm($this->group->project))
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

    public function viewTaskAction(): Action
    {
        return ViewAction::make('viewTask')
            ->mountUsing(function (array $arguments) {
                $this->fillRichEditorField($arguments['task_id']);
            })
            ->modalHeading('')
            ->modal()
            ->closeModalByClickingAway(false)
            ->slideOver()
            ->modalWidth('6xl')
            ->record(fn (array $arguments) => Task::find($arguments['task_id']))
            ->modalContent(fn($record, array $arguments) => view('filament.resources.project-resource.widgets.view-task', ['task' => $record]));
    }

    public function fillRichEditorField($taskId)
    {
        $task = Task::find($taskId);
        if ($task)
            $this->richEditorFieldForm->fill([
                'description' => $this->task->description ?? ''
            ]);
        else
            $this->richEditorFieldForm->fill([
                'description' => ''
            ]);
    }

    public function richEditorFieldForm(Form $form): Form
    {
        return $form
            ->extraAttributes([
                'class' => 'w-full'
            ])
            ->model(Task::class)
            ->schema([
                RichEditor::make('description')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory(fn() => $this->task ? 'tasks/' . $this->task->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                    ->label(__('task.form.description')),
            ]);
    }

    public function saveRichEditorDescription($task)
    {
        $richData = $this->richEditorFieldForm->getState();

        $task = $this->task;

        if (isset($richData['description']) && trim($richData['description']) != '') {
            $modifiedDescription = $this->processDescription($richData['description']);
        } else {
            $modifiedDescription = '';
        }

        $task->update([
            'description' => $modifiedDescription
        ]);

        $this->showNotification(__('task.description_updated'));
    }

    public function fillFileUploadField()
    {
        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);
    }

    public function fileUploadFieldForm(Form $form): Form
    {
        return $form
            ->live()
            ->extraAttributes([
                'class' => 'w-full'
            ])
            ->model(Task::class)
            ->schema([
                FileUpload::make('attachments')
                    ->columnSpanFull()
                    ->multiple()
                    ->hiddenLabel()
                    ->previewable()
                    ->downloadable()
                    ->multiple()
                    ->appendFiles()
                    ->reorderable()
                    ->preserveFilenames()
                    ->visibility('private')
                    ->openable()
                    ->directory(fn() => $this->task ? 'tasks/' . $this->task->id . '/attachments' : 'tasks/' . Task::latest()->first()->id + 1 . '/attachments')
                    ->label(__('task.form.attachments')),
            ]);
    }

    public function saveFileUploadAttachments()
    {
        $fileData = $this->fileUploadFieldForm->getState();

        $task = $this->task;

        $attachments = $task->attachments;

        foreach ($fileData['attachments'] as $attachment) {
            $attachments[] = $attachment;
        }

        $task->update([
            'attachments' => $attachments
        ]);

        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);

        $this->showNotification(__('task.attachment_added'));
    }

    public function cancelFileUploadAttachments()
    {
        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);
    }

    protected function getForms(): array
    {
        return [
            'richEditorFieldForm',
            'fileUploadFieldForm'
        ];
    }

    public function addCommitAction(): Action
    {
        return Action::make('addCommit')
            ->hiddenLabel()
            ->iconButton()
            ->modal()
            ->icon('heroicon-o-plus')
            ->tooltip(__('task.add_commit_number'))
            ->modalHeading(__('task.add_commit'))
            ->form([
                TextInput::make('commitNumber')
                    ->label(__('task.number'))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $task = Task::find($arguments['task']);

                $commitNumbers = $task->commit_numbers ?? [];

                if ($data['commitNumber'] && !in_array($data['commitNumber'], $commitNumbers)) {
                    if ($commitNumbers) {
                        $commitNumbers[] = $data['commitNumber'];
                    } else {
                        $commitNumbers = [$data['commitNumber']];
                    }

                    $task->update([
                        'commit_numbers' => $commitNumbers
                    ]);

                    if ($task->creator && !$task->creator->hasRole('Client'))
                        SendEmailJob::dispatch(NewCommitMail::class, $task->creator, $task, auth()->user(), $data['commitNumber']);

                    $this->showNotification(__('task.commit_number_added'));

                    $this->replaceMountedAction('viewTask', ['task_id' => $task->id]);
                }
            })
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false)
            ->extraModalFooterActions(function () {
                return [
                    Action::make('closeModal')
                        ->label(__('task.close'))
                        ->color('gray')
                        ->action(fn(array $arguments) => $this->replaceMountedAction('viewTask', ['task_id' => $arguments['task']]))
                ];
            });
    }
}
