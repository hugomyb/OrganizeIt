<?php

namespace App\Livewire;

use App\Concerns\CanManageTasksInfo;
use App\Concerns\CanProcessDescription;
use App\Concerns\CanShowNotification;
use App\Jobs\SendEmailJob;
use App\Mail\AssignToTaskMail;
use App\Mail\NewCommentMail;
use App\Mail\NewCommitMail;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class ModalContent extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use CanShowNotification;
    use InteractsWithActions;
    use CanProcessDescription;
    use CanManageTasksInfo;

    public Task $task;

    public $description;
    public $attachments;
    public $comment;

    public function mount($task)
    {
        $this->task = $task;

        $this->fillRichEditorField();
    }

    protected function getForms(): array
    {
        return [
            'richEditorFieldForm',
            'fileUploadFieldForm',
        ];
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

    public function saveTaskTitle($title)
    {
        $task = $this->task;

        if ($title !== $task->title) {
            $task->title = $title;
            $task->save();

            $this->showNotification(__('task.title_updated'));
        }
    }

    public function fillFileUploadField()
    {
        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);
    }

    public function render()
    {
        return view('livewire.modal-content');
    }
}
