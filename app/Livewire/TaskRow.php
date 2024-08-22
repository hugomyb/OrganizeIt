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
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
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


    public function assignUserToTask($userId, $taskId)
    {
        $task = Task::find($taskId);
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

    public function toggleUserToTask($userId, $taskId)
    {
        $task = Task::find($taskId);
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

    public function saveTaskTitle($taskId, $title)
    {
        $task = Task::find($taskId);

        if ($title !== $task->title) {
            $task->title = $title;
            $task->save();

            $this->showNotification(__('task.title_updated'));
        }
    }
}
