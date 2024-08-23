<?php

namespace App\Livewire;

use App\Concerns\CanProcessDescription;
use App\Concerns\CanShowNotification;
use App\Concerns\InteractsWithTaskForm;
use App\Jobs\SendEmailJob;
use App\Mail\AssignToTaskMail;
use App\Mail\ChangeTaskPriorityMail;
use App\Mail\ChangeTaskStatusMail;
use App\Mail\NewCommentMail;
use App\Mail\NewCommitMail;
use App\Mail\NewTaskMail;
use App\Models\Comment;
use App\Models\Priority;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Facades\Storage;
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

    public Task $task;
    public $sortBy;

    public $description;
    public $attachments;
    public $comment;

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

    public function viewTaskAction(): Action
    {
        return ViewAction::make('viewTask')
            ->mountUsing(function () {
                $this->fillRichEditorField();
            })
            ->modalHeading('')
            ->modal()
            ->closeModalByClickingAway(false)
            ->slideOver()
            ->modalWidth('6xl')
            ->record($this->task)
            ->modalContent(fn($record, array $arguments) => view('filament.resources.project-resource.widgets.view-task', ['task' => $record]));
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

    public function setTaskStatus($statusId)
    {
        $task = $this->task;

        $oldStatusId = $task->status_id;
        $oldStatus = Status::find($oldStatusId);

        if ($statusId != $task->status_id) {
            if ($statusId === Status::getCompletedStatusId()) {
                $task->update(['status_id' => $statusId, 'completed_at' => now()]);
            } else {
                $task->update(['status_id' => $statusId, 'completed_at' => null]);
            }

            $task->refresh();

            $users = $task->project->users;

            foreach ($users as $user) {
                if ($user->hasRole('Client')) {
                    if ($task->status->id === Status::getCompletedStatusId()) {
                        SendEmailJob::dispatch(ChangeTaskStatusMail::class, $user, $task, auth()->user(), $oldStatus, $user);
                    }
                } else {
                    SendEmailJob::dispatch(ChangeTaskStatusMail::class, $user, $task, auth()->user(), $oldStatus, $user);
                }
            }

            $this->showNotification(__('status.status_updated'));
        }
    }

    public function setTaskPriority($priorityId)
    {
        $task = $this->task;

        $oldPriorityId = $task->priority_id;
        $oldPriority = Priority::find($oldPriorityId);

        if ($priorityId != $task->priority_id) {
            $task->update(['priority_id' => $priorityId]);

            $task->refresh();

            $users = $task->project->users;

            foreach ($users as $user) {
                if (!$user->hasRole('Client')) {
                    SendEmailJob::dispatch(ChangeTaskPriorityMail::class, $user, $task, auth()->user(), $oldPriority);
                }
            }

            $this->showNotification(__('priority.priority_updated'));
        }
    }

    public function deleteAttachment($taskId, $attachment)
    {
        $task = $this->task;
        $previousAttachments = $task->attachments;

        $attachments = collect($previousAttachments)->filter(function ($previousAttachments) use ($attachment) {
            return $previousAttachments !== $attachment;
        })->values();

        Storage::disk('public')->delete($attachment);

        $task->update([
            'attachments' => $attachments
        ]);

        $this->showNotification(__('task.attachment_removed'));
    }

    public function sendComment()
    {
        $task = $this->task;

        if ($this->comment !== null && trim($this->comment) !== '') {
            $comment = $task->comments()->create([
                'user_id' => auth()->id(),
                'content' => $this->comment
            ]);

            $this->comment = '';

            $users = $task->users;
            foreach ($users as $user) {
                if (!$user->hasRole('Client')) {
                    SendEmailJob::dispatch(NewCommentMail::class, $user, $task, $comment);
                }
            }

            $this->showNotification(__('task.comment_added'));
            $this->dispatch('commentSent');
        }
    }

    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        $comment->delete();

        $this->showNotification(__('task.comment_removed'));
    }

    #[On('modal-closed')]
    public function modalClosed()
    {
        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);
    }

    public function deleteCommitNumber($commit)
    {
        $task = $this->task;

        $commitNumbers = $task->commit_numbers;

        $commitNumbers = collect($commitNumbers)->filter(function ($commitNumbers) use ($commit) {
            return $commitNumbers !== $commit;
        })->values();

        $task->update([
            'commit_numbers' => $commitNumbers
        ]);

        $this->showNotification(__('task.commit_number_removed'));
    }

    public function updateDatesAction(): Action
    {
        return Action::make('updateDates')
            ->fillForm(function () {
                $record = $this->task;

                return [
                    'start_date' => $record->start_date,
                    'due_date' => $record->due_date
                ];
            })
            ->modal()
            ->modalHeading(__('task.update_dates'))
            ->form([
                Grid::make(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('task.start_date')),

                        DatePicker::make('due_date')
                            ->label(__('task.end_date')),
                    ])
            ])
            ->action(function ($data): void {
                $task = $this->task;

                $task->update([
                    'start_date' => $data['start_date'] ?? null,
                    'due_date' => $data['due_date'] ?? null
                ]);

                $this->showNotification(__('task.dates_updated'));

                $this->replaceMountedAction('viewTask', ['task_id' => $task->id]);
            })
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false)
            ->extraModalFooterActions(function () {
                return [
                    Action::make('closeModal')
                        ->label(__('task.close'))
                        ->color('gray')
                        ->action(fn() => $this->replaceMountedAction('viewTask', ['task_id' => $this->task->id]))
                ];
            });
    }


    public function assignUserToTask($userId)
    {
        $task = $this->task;
        $user = User::find($userId);
        if ($task) {
            if (!$task->users()->where('user_id', $userId)->exists()) {
                $task->users()->attach($userId);
            }
        }

        if (!auth()->user()->hasRole('Client')) {
            SendEmailJob::dispatch(AssignToTaskMail::class, $user, $task, auth()->user());
        }

        $this->showNotification(__('user.assigned'));
    }

    public function toggleUserToTask($userId)
    {
        $task = $this->task;
        $user = User::find($userId);
        if ($task) {
            if ($task->users()->where('user_id', $userId)->exists()) {
                $task->users()->detach($userId);

                $this->showNotification(__('user.unassigned'));
            } else {
                $task->users()->attach($userId);

                if (!auth()->user()->hasRole('Client')) {
                    SendEmailJob::dispatch(AssignToTaskMail::class, $user, $task, auth()->user());
                }

                $this->showNotification(__('user.assigned'));
            }
        }
    }

    public function saveTaskTitle($title)
    {
        $task = $this->task;

        if ($title !== $task->title) {
            $task->title = $title;
            $task->save();

            $this->showNotification(__('task.title_updated'));
        }
    }

    public function fillRichEditorField()
    {
        $task = $this->task;
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

    public function saveRichEditorDescription()
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
            ->action(function (array $data): void {
                $task = $this->task;

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
                        ->action(fn() => $this->replaceMountedAction('viewTask', ['task_id' => $this->task->id]))
                ];
            });
    }

    public function openTaskById()
    {
        $this->mountAction('viewTask', ['task_id' => $this->task->id]);
    }
}
