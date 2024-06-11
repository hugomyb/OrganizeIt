<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Concerns\InteractsWithTooltipActions;
use App\Filament\Resources\ProjectResource;
use App\Jobs\SendEmailJob;
use App\Mail\AssignToProjectMail;
use App\Mail\AssignToTaskMail;
use App\Mail\ChangeTaskPriorityMail;
use App\Mail\ChangeTaskStatusMail;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\CanAuthorizeResourceAccess;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Stichoza\GoogleTranslate\GoogleTranslate;

class ShowProject extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    use InteractsWithTooltipActions;
    use CanAuthorizeResourceAccess;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.show-project';

    public $record;
    public $currentTask;
    public $groups;

    public $statusFilters;
    public $priorityFilters;

    public $toggleCompletedTasks = true;

    public $description;
    public $attachments;

    public $comment;

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

    public function openTaskById($taskId)
    {
        if (Task::where('id', $taskId)->exists() && Task::where('id', $taskId)->where('project_id', $this->record->id)->exists()) {
            $this->mountAction('viewTask', ['task_id' => $taskId]);
        }
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
                ->visible(auth()->user()->hasPermission('manage_tasks'))
                ->modalWidth('7xl')
                ->model(Task::class)
                ->label(__('task.add_task'))
                ->icon('heroicon-o-plus')
                ->modalHeading(__('task.add_task'))
                ->form($this->getTaskForm())
                ->modalSubmitActionLabel(__('task.add'))
                ->modalCancelAction(fn(StaticAction $action, $data) => $action->action('cancelCreateTask'))
                ->action(function (array $data): void {
                    $lastTask = Task::where('group_id', $data['group_id'])->orderBy('order', 'desc')->first();
                    $this->record->tasks()->create(array_merge($data, [
                        'order' => $lastTask ? $lastTask->order + 1 : 0,
                        'created_by' => auth()->id()
                    ]));

                    $this->showNotification(__('task.task_added'));
                }),
        ];
    }

    public function createGroupAction(): Action
    {
        return Action::make('createGroup')
            ->outlined()
            ->label(__('group.create_group'))
            ->form([
                TextInput::make('name')
                    ->autofocus()
                    ->label(__('group.name'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->record->groups()->create($data);

                $this->showNotification(__('group.group_created'));
            });
    }

    public function editGroupAction(): Action
    {
        return EditAction::make('editGroup')
            ->record(fn(array $arguments) => Group::find($arguments['group_id']))
            ->modalHeading(__('group.edit_group'))
            ->form([
                TextInput::make('name')
                    ->autofocus()
                    ->label(__('group.name'))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                Group::find($arguments['group_id'])->update($data);

                $this->showNotification(__('group.group_updated'));
            });
    }

    public function deleteGroupAction(): Action
    {
        return Action::make('deleteGroup')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading(fn(array $arguments) => __('group.delete_group') . ' "' . Str::limit(Group::find($arguments['group_id'])->name, 20) . '" ?')
            ->record(fn(array $arguments) => Group::find($arguments['group_id']))
            ->action(function (array $arguments): void {
                Group::find($arguments['group_id'])->delete();

                $this->showNotification(__('group.group_deleted'));
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
                ->label(__('group.group'))
                ->default($groupId ?? $this->record->groups->first()->id)
                ->required()
                ->options($this->record->groups->pluck('name', 'id')),

            TextInput::make('title')
                ->autofocus()
                ->label(__('task.form.title'))
                ->columnSpanFull()
                ->live()
                ->required(),

            RichEditor::make('description')
                ->columnSpanFull()
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory(fn($record) => $record ? 'tasks/' . $record->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                ->label('Description'),

            Select::make('status_id')
                ->label(__('task.form.status'))
                ->default(Status::whereName('À faire')->first()->id)
                ->preload()
                ->searchable()
                ->options($statusOptions)
                ->allowHtml()
                ->createOptionForm([
                    TextInput::make('name')
                        ->live(onBlur: true)
                        ->unique('statuses', ignoreRecord: true)
                        ->label(__('status.table.name'))
                        ->afterStateUpdated(function (Set $set, $state) {
                            $translate = app()->make(GoogleTranslate::class);
                            $translate->setSource('fr');
                            $translate->setTarget('en');
                            $result = $translate->translate($state ?? "");

                            $set('en_name', $result);
                        })
                        ->required(),

                    TextInput::make('en_name')
                        ->live(onBlur: true)
                        ->unique('statuses', ignoreRecord: true)
                        ->label(__('status.table.en_name'))
                        ->required(),

                    ColorPicker::make('color')
                        ->label(__('status.table.color'))
                ])->createOptionUsing(fn(array $data) => Status::create($data)->getKey())
                ->required(),

            Select::make('priority_id')
                ->label(__('task.form.priority'))
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
                ->reorderable()
                ->visibility('private')
                ->openable()
                ->directory(fn($record) => $record ? 'tasks/' . $record->id . '/attachments' : 'tasks/' . Task::latest()->first()->id + 1 . '/attachments')
                ->label(__('task.form.attachments'))
        ];
    }

    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->icon('heroicon-o-plus')
            ->link()
            ->modalWidth('7xl')
            ->label(__('task.add_task'))
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

                $this->showNotification(__('task.task_added'));
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

        $oldStatusId = $task->status_id;
        $oldStatus = Status::find($oldStatusId);

        if ($statusId != $task->status_id) {
            if ($statusId === Status::whereName('Terminé')->first()->id) {
                $task->update(['status_id' => $statusId, 'completed_at' => now()]);
            } else {
                $task->update(['status_id' => $statusId, 'completed_at' => null]);
            }

            $users = $task->project->users;

            foreach ($users as $user) {
                SendEmailJob::dispatch(ChangeTaskStatusMail::class, $user, $task, auth()->user(), $oldStatus);
            }

            $this->showNotification(__('status.status_updated'));
        }
    }

    public function setTaskPriority($taskId, $priorityId)
    {
        $task = Task::find($taskId);

        $oldPriorityId = $task->priority_id;
        $oldPriority = Priority::find($oldPriorityId);

        if ($priorityId != $task->priority_id) {
            $task->update(['priority_id' => $priorityId]);

            $users = $task->project->users;

            foreach ($users as $user) {
                SendEmailJob::dispatch(ChangeTaskPriorityMail::class, $user, $task, auth()->user(), $oldPriority);
            }

            $this->showNotification(__('priority.priority_updated'));
        }
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
        $user = User::find($userId);
        if ($task) {
            if (!$task->users()->where('user_id', $userId)->exists()) {
                $task->users()->attach($userId);
            }
        }

        SendEmailJob::dispatch(AssignToTaskMail::class, $user, $task, auth()->user());

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

                SendEmailJob::dispatch(AssignToTaskMail::class, $user, $task, auth()->user());

                $this->showNotification(__('user.assigned'));
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

        $user = User::find($userId);

        SendEmailJob::dispatch(AssignToProjectMail::class, $user, $this->record, auth()->user());

        $this->showNotification(__('user.added'));
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

            $this->showNotification(__('task.title_updated'));
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
        $this->currentTask = Task::find($task['id']);
        if ($this->currentTask)
            $this->richEditorFieldForm->fill([
                'description' => $task['description'] ?? ''
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
            ->model($this->currentTask)
            ->schema([
                RichEditor::make('description')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory(fn() => $this->currentTask ? 'tasks/' . $this->currentTask->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                    ->label(__('task.form.description')),
            ]);
    }

    public function saveRichEditorDescription($task)
    {
        $richData = $this->richEditorFieldForm->getState();

        $task = Task::find($task['id']);

        $task->update([
            'description' => $richData['description']
        ]);

        $this->richEditorFieldForm->fill([
            'description' => ''
        ]);

        $this->currentTask = null;

        $this->showNotification(__('task.description_updated'));
    }

    public function cancelRichEditorDescription()
    {
        $this->richEditorFieldForm->fill([
            'description' => ''
        ]);

        $this->currentTask = null;
    }

    protected function getForms(): array
    {
        return [
            'richEditorFieldForm',
            'fileUploadFieldForm'
        ];
    }

    public function deleteAttachment($taskId, $attachment)
    {
        $task = Task::find($taskId);
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

    public function fillFileUploadField($taskId)
    {
        $this->currentTask = Task::find($taskId);

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
            ->model($this->currentTask)
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
                    ->directory(fn() => $this->currentTask ? 'tasks/' . $this->currentTask->id . '/attachments' : 'tasks/' . Task::latest()->first()->id + 1 . '/attachments')
                    ->label(__('task.form.attachments')),
            ]);
    }

    public function saveFileUploadAttachments($taskId)
    {
        $fileData = $this->fileUploadFieldForm->getState();

        $task = Task::find($taskId);

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

        $this->currentTask = null;

        $this->showNotification(__('task.attachment_added'));
    }

    public function cancelFileUploadAttachments()
    {
        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);

        $this->currentTask = null;
    }

    public function sendComment($taskId)
    {
        $task = Task::find($taskId);

        $task->comments()->create([
            'user_id' => auth()->id(),
            'content' => $this->comment
        ]);

        $this->comment = '';

        $this->showNotification(__('task.comment_added'));
        $this->dispatch('commentSent');
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
        $this->richEditorFieldForm->fill([
            'description' => ''
        ]);

        $this->fileUploadFieldForm->fill([
            'attachments' => []
        ]);

        $this->currentTask = null;
    }


    // ======== MAILS ========
//    #[On('assignUserToTaskMail')]
//    public function sendAssignUserToTaskMail($taskId, $userId)
//    {
//        $task = Task::find($taskId);
//        $user = User::find($userId);
//        $author = auth()->user();
//
//        Mail::to($user)->send(new AssignToTaskMail($task, $author));
//    }
//
//    #[On('assignUserToProjectMail')]
//    public function sendAssignUserToProjectMail($userId)
//    {
//        $user = User::find($userId);
//        $author = auth()->user();
//
//        Mail::to($user)->send(new AssignToProjectMail($this->record, $author));
//    }
//
//    #[On('changeTaskStatusMail')]
//    public function sendChangeTaskStatusMail($taskId, $oldStatusId)
//    {
//        $task = Task::find($taskId);
//        $author = auth()->user();
//        $users = $task->project->users;
//        $oldStatus = Status::find($oldStatusId);
//
//        foreach ($users as $user) {
//            Mail::to($user)->send(new ChangeTaskStatusMail($task, $author, $oldStatus));
//        }
//    }
}
