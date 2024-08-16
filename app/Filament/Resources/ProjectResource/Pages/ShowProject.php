<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Concerns\CanShowNotification;
use App\Concerns\InteractsWithTooltipActions;
use App\Filament\Resources\ProjectResource;
use App\Jobs\SendEmailJob;
use App\Mail\AssignToProjectMail;
use App\Mail\AssignToTaskMail;
use App\Mail\ChangeTaskPriorityMail;
use App\Mail\ChangeTaskStatusMail;
use App\Mail\NewCommentMail;
use App\Mail\NewCommitMail;
use App\Mail\NewTaskMail;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use DOMDocument;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\StaticAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
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
    use CanShowNotification;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.show-project';

    public function getExtraBodyAttributes(): array
    {
        return [
            'id' => 'project-page'
        ];
    }

    public $record;
    public $currentTask;
    public $groups;

    public $search;
    public $statusFilters;
    public $priorityFilters;
    public string $sortBy = 'default';

    public $toggleCompletedTasks;

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

    public function mount($record): void
    {
        $this->record = Project::with(['users', 'groups.tasks.status', 'groups.tasks.priority'])->find($record);
        $this->getStatusFilters();
        $this->getPriorityFilters();
        $this->getSortByFilter();

        if ($this->statusFilters->contains('name', 'Terminé')) {
            $this->toggleCompletedTasks = true;
        } elseif ($this->statusFilters->isEmpty()) {
            $this->toggleCompletedTasks = true;
        } else {
            $this->toggleCompletedTasks = false;
        }
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
        $sortBy = $this->sortBy;
        $search = $this->search;

        $completedStatus = Status::where('name', 'Terminé')->first();
        $completedStatusId = $completedStatus ? $completedStatus->id : null;

        // Charger toutes les relations nécessaires pour éviter les problèmes de lazy loading
        $this->groups = Group::with([
            'tasks' => function ($query) use ($statusIds, $priorityIds, $sortBy, $search) {
                $query->where(function ($query) use ($statusIds, $priorityIds, $search) {
                    if (!empty($statusIds)) {
                        $query->whereIn('status_id', $statusIds);
                    }
                    if (!empty($priorityIds)) {
                        $query->whereIn('priority_id', $priorityIds);
                    }
                    if (!empty($search)) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'like', '%' . $search . '%')
                                ->orWhere('id', $search);
                        });
                    }
                })
                    ->orWhereHas('children', function ($query) use ($statusIds, $priorityIds, $search) {
                        $query->where(function ($query) use ($statusIds, $priorityIds, $search) {
                            if (!empty($statusIds)) {
                                $query->whereIn('status_id', $statusIds);
                            }
                            if (!empty($priorityIds)) {
                                $query->whereIn('priority_id', $priorityIds);
                            }
                            if (!empty($search)) {
                                $query->where(function ($query) use ($search) {
                                    $query->where('title', 'like', '%' . $search . '%')
                                        ->orWhere('id', $search);
                                });
                            }
                        });
                    });

                if ($sortBy === 'priority') {
                    $query->orderByDesc('priority_id');
                } else {
                    $query->orderBy('order');
                }

                // Précharger toutes les relations nécessaires
                $query->with(['children', 'users', 'status', 'comments', 'priority', 'creator', 'project']);
            }
        ])->where('project_id', $this->record->id)->get();

        // Préparez les styles pour chaque tâche
        $this->groups->each(function ($group) use ($completedStatusId) {
            $group->tasks->each(function ($task) use ($completedStatusId) {
                $task->style = $task->status->id == $completedStatusId ? 'opacity: 0.4' : '';
            });
        });
    }

    protected function applyRecursiveFilters($query, $statusIds, $priorityIds, $sortBy, $search)
    {
        $query->where(function ($query) use ($statusIds, $priorityIds, $search) {
            if (!empty($statusIds)) {
                $query->whereIn('status_id', $statusIds);
            }
            if (!empty($priorityIds)) {
                $query->whereIn('priority_id', $priorityIds);
            }
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%')
                        ->orWhere('id', $search);
                });
            }
        })
            ->orWhereHas('children', function ($query) use ($statusIds, $priorityIds, $sortBy, $search) {
                $query->where(function ($query) use ($statusIds, $priorityIds, $search) {
                    if (!empty($statusIds)) {
                        $query->whereIn('status_id', $statusIds);
                    }
                    if (!empty($priorityIds)) {
                        $query->whereIn('priority_id', $priorityIds);
                    }
                    if (!empty($search)) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'like', '%' . $search . '%')
                                ->orWhere('id', $search);
                        });
                    }
                });

                if ($sortBy === 'priority') {
                    $query->orderByDesc('priority_id');
                } else {
                    $query->orderBy('order');
                }

                // Appel récursif pour les enfants
                $query->with(['children', 'users', 'status', 'comments', 'priority', 'creator', 'project']);
            });

        if ($sortBy === 'priority') {
            $query->orderBy('priority_id');
        } else {
            $query->orderBy('order');
        }
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
                ->closeModalByClickingAway(false)
                ->icon('heroicon-o-plus')
                ->modalHeading(__('task.add_task'))
                ->form($this->getTaskForm())
                ->modalSubmitActionLabel(__('task.add'))
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
                }),

            ActionGroup::make([
                Action::make('export_completed_tasks')
                    ->label(__('task.export_completed_tasks'))
                    ->icon('uni-file-export-o')
                    ->visible(auth()->user()->hasPermission('export_tasks'))
                    ->form([
                        Fieldset::make(__('task.form.period'))
                            ->columns(2)
                            ->live()
                            ->schema([
                                Select::make('period')
                                    ->columnSpanFull()
                                    ->default('this_week')
                                    ->selectablePlaceholder(false)
                                    ->options([
                                        'today' => __('task.form.today'),
                                        'yesterday' => __('task.form.yesterday'),
                                        'this_week' => __('task.form.this_week'),
                                        'last_week' => __('task.form.last_week'),
                                        'this_month' => __('task.form.this_month'),
                                        'last_month' => __('task.form.last_month'),
                                        'custom' => __('task.form.custom'),
                                    ])
                                    ->label(__('task.form.period'))
                                    ->required(),

                                DatePicker::make('start_date')
                                    ->label(__('task.form.start_date'))
                                    ->placeholder(__('task.form.select_date'))
                                    ->native(false)
                                    ->visible(fn($get) => $get('period') === 'custom')
                                    ->required(),

                                DatePicker::make('end_date')
                                    ->label(__('task.form.end_date'))
                                    ->placeholder(__('task.form.select_date'))
                                    ->native(false)
                                    ->visible(fn($get) => $get('period') === 'custom')
                                    ->required(),
                            ]),

                        // taches sur cette periode
                        Fieldset::make(__('task.exported_tasks'))
                            ->visible(fn($get) => ($get('start_date') && $get('end_date')) || $get('period') !== 'custom')
                            ->live()
                            ->columns(1)
                            ->schema([
                                Placeholder::make('exported_tasks')
                                    ->hiddenLabel()
                                    ->content(function ($get) {
                                        $tasks = collect();

                                        if ($get('period') === 'today') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', now()->startOfDay())
                                                ->where('completed_at', '<=', now()->endOfDay())
                                                ->get();
                                        } elseif ($get('period') === 'yesterday') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', now()->subDay()->startOfDay())
                                                ->where('completed_at', '<=', now()->subDay()->endOfDay())
                                                ->get();
                                        } elseif ($get('period') === 'this_week') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', now()->startOfWeek())
                                                ->where('completed_at', '<=', now()->endOfWeek())
                                                ->get();
                                        } elseif ($get('period') === 'last_week') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', now()->subWeek()->startOfWeek())
                                                ->where('completed_at', '<=', now()->subWeek()->endOfWeek())
                                                ->get();
                                        } elseif ($get('period') === 'this_month') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', now()->startOfMonth())
                                                ->where('completed_at', '<=', now()->endOfMonth())
                                                ->get();
                                        } elseif ($get('period') === 'last_month') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', now()->subMonth()->startOfMonth())
                                                ->where('completed_at', '<=', now()->subMonth()->endOfMonth())
                                                ->get();
                                        } elseif ($get('period') === 'custom') {
                                            $tasks = Task::where('project_id', $this->record->id)
                                                ->where('completed_at', '>=', $get('start_date'))
                                                ->where('completed_at', '<=', $get('end_date'))
                                                ->get();
                                        }

                                        return view('tasks.exported-tasks', compact('tasks'));
                                    })
                            ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
            ])->visible(auth()->user()->hasPermission('export_tasks')),
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
        static $statusOptions = null;
        static $priorityOptions = null;

        if (!$statusOptions) {
            $statusOptions = Status::all()->mapWithKeys(function ($status) {
                $iconHtml = view('components.status-icon', ['status' => $status])->render();
                return [$status->id => $iconHtml];
            })->toArray();
        }

        if (!$priorityOptions) {
            $priorityOptions = Priority::all()->mapWithKeys(function ($priority) {
                $iconHtml = view('components.priority-icon', ['priority' => $priority])->render();
                return [$priority->id => $iconHtml];
            })->toArray();
        }

        return [
            Select::make('group_id')
                ->preload()
                ->searchable()
                ->label(__('group.group'))
                ->default($groupId ?? $this->record->groups->first()->id ?? null)
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

            \Filament\Forms\Components\Group::make([
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
                            ->suffixAction(\Filament\Forms\Components\Actions\Action::make('randomize')
                                ->label(__('project.form.color.randomize'))
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn($set) => $set('color', '#' . bin2hex(random_bytes(3))))
                            )
                            ->unique(ignoreRecord: true)
                            ->label(__('status.table.color'))
                    ])->createOptionUsing(fn(array $data) => Status::create($data)->getKey())
                    ->required(),

                Select::make('priority_id')
                    ->label(__('task.form.priority'))
                    ->preload()
                    ->searchable()
                    ->default(Priority::whereName('Aucune')->first()->id)
                    ->disabled(fn() => auth()->user()->hasPermission('change_priority') ? false : true)
                    ->dehydrated()
                    ->options($priorityOptions)
                    ->allowHtml()
                    ->required(),
            ])->columns(2),

            Select::make('users')
                ->relationship(
                    name: 'users',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn(Builder $query) => $this->record->users()->getQuery()
                )
                ->label(__('task.assign_to'))
                ->dehydrated()
                ->disabled(fn() => !auth()->user()->hasPermission('assign_user'))
                ->visible(fn() => auth()->user()->hasPermission('view_assigned_users'))
                ->preload()
                ->searchable()
                ->multiple(),

            \Filament\Forms\Components\Group::make([
                DatePicker::make('start_date')
                    ->label(__('task.start_date')),

                DatePicker::make('due_date')
                    ->label(__('task.end_date')),
            ])->visible(fn() => auth()->user()->hasPermission('manage_dates') ? true : false)
                ->columns(2),

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
        return CreateAction::make('createTask')
            ->icon('heroicon-o-plus')
            ->link()
            ->modalWidth('7xl')
            ->model(Task::class)
            ->label(__('task.add_task'))
            ->form(function ($livewire, array $arguments) {
                $group_id = $arguments['group_id'];

                return $this->getTaskForm($group_id);
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

    public function viewTaskAction(): Action
    {
        return ViewAction::make('viewTask')
            ->mountUsing(function ($arguments) {
                $task = Task::find($arguments['task_id']);
                $this->currentTask = $task;
                $this->fillRichEditorField();
            })
            ->modalHeading('')
            ->closeModalByClickingAway(false)
            ->slideOver()
            ->modalWidth('6xl')
            ->record(fn(array $arguments) => Task::with(['project', 'creator', 'parent', 'status', 'priority', 'users', 'comments', 'children'])->find($arguments['task_id']))
            ->modalContent(fn($record) => view('filament.resources.project-resource.widgets.view-task', ['task' => $record]));
    }

    public function editTaskAction(): Action
    {
        return EditAction::make('editTask')
            ->modalWidth('7xl')
            ->label('Éditer')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm())
            ->action(function (array $data, $record): void {
                $task = $record;

                if (isset($data['description']) && trim($data['description']) != '') {
                    $data['description'] = $this->processDescription($data['description']);
                }

                $task->update($data);

                $this->showNotification(__('task.task_updated'));
            });
    }

    public function setTaskStatus($taskId, $statusId)
    {
        $task = Task::find($taskId);

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

    public function setTaskPriority($taskId, $priorityId)
    {
        $task = Task::find($taskId);

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

    public function updateStatusFilter($statusId)
    {
        $status = Status::find($statusId);
        if ($status) {
            if ($this->statusFilters->contains('id', $statusId)) {
                $this->statusFilters = $this->statusFilters->reject(fn($filter) => $filter['id'] === $statusId)->values();
            } else {
                $this->statusFilters->push($status);
            }

            if ($this->statusFilters->contains('name', 'Terminé')) {
                $this->toggleCompletedTasks = true;
            } else {
                $this->toggleCompletedTasks = false;
            }

            $filters = $this->statusFilters->toArray();
            Cookie::queue('status_filters', json_encode($filters), 60 * 24 * 30);
        }
    }

    public function getStatusFilters()
    {
        $cookie = Cookie::get('status_filters');
        if ($cookie) {
            $this->statusFilters = collect(json_decode($cookie, true));
        } else {
            $this->statusFilters = collect(); // Par défaut, pas de filtres
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

            $filters = $this->priorityFilters->toArray();
            Cookie::queue('priority_filters', json_encode($filters), 60 * 24 * 30);
        }
    }

    public function getPriorityFilters()
    {
        $cookie = Cookie::get('priority_filters');
        if ($cookie) {
            $this->priorityFilters = collect(json_decode($cookie, true));
        } else {
            $this->priorityFilters = collect(); // Par défaut, pas de filtres
        }
    }

    public function getSortByFilter()
    {
        $cookie = Cookie::get('sort_by');
        if ($cookie) {
            $this->sortBy = $cookie;
        } else {
            $this->sortBy = 'default'; // Par défaut, pas de filtres
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

        $filters = $this->statusFilters->toArray();
        Cookie::queue('status_filters', json_encode($filters), 60 * 24 * 30);
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

        $project = Project::find($record);

        return auth()->user()->projects->contains($project);
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

    function processDescription($htmlContent)
    {
        $dom = new DOMDocument();
        // Load HTML content
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        // Get all <a> elements
        $links = $dom->getElementsByTagName('a');
        $imgs = $dom->getElementsByTagName('img');

        foreach ($links as $link) {
            // Set target attribute to _blank and style to color blue
            $link->setAttribute('target', '_blank');
            $link->setAttribute('style', 'color: blue;');
        }

        foreach ($imgs as $img) {
            // Set lazy loading attribute to lazy
            $img->setAttribute('loading', 'lazy');

            // Check if img is inside an <a> element
            $parent = $img->parentNode;
            if ($parent->nodeName !== 'a') {
                // Create <a> element
                $a = $dom->createElement('a');
                $a->setAttribute('href', $img->getAttribute('src'));
                $a->setAttribute('target', '_blank');
                $a->setAttribute('style', 'color: blue;');

                // Replace img with the new <a> element containing the img
                $parent->replaceChild($a, $img);
                $a->appendChild($img);
            }
        }

        // Convert text URLs to <a> elements with target="_blank" and style
        $body = $dom->getElementsByTagName('body')->item(0);
        $this->convertTextUrlsToLinks($body, $dom);

        // Save and return modified HTML
        return $dom->saveHTML($dom->documentElement);
    }

    function convertTextUrlsToLinks($node, $dom)
    {
        if ($node->nodeType == XML_TEXT_NODE) {
            $text = $node->nodeValue;
            $newHtml = preg_replace(
                '#(https?://[^\s<]+)#i',
                '<a href="$1" target="_blank" style="color: blue;">$1</a>',
                htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            );
            if ($newHtml !== htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) {
                $newFragment = $dom->createDocumentFragment();
                $newFragment->appendXML($newHtml);
                $node->parentNode->replaceChild($newFragment, $node);
            }
        } elseif ($node->nodeType == XML_ELEMENT_NODE) {
            foreach ($node->childNodes as $child) {
                $this->convertTextUrlsToLinks($child, $dom);
            }
        }
    }

    public function fillRichEditorField()
    {
        if ($this->currentTask)
            $this->richEditorFieldForm->fill([
                'description' => $this->currentTask->description ?? ''
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

        $this->currentTask = null;
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

                    if (!$task->creator->hasRole('Client'))
                        SendEmailJob::dispatch(NewCommitMail::class, $task->creator, $task, auth()->user(), $data['commitNumber']);

                    $this->showNotification(__('task.commit_number_added'));

                    $this->replaceMountedAction('viewTask', ['task_id' => $task->id]);
                }
            })
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false)
            ->extraModalFooterActions(function (array $arguments) {
                return [
                    Action::make('closeModal')
                        ->label(__('task.close'))
                        ->color('gray')
                        ->action(fn() => $this->replaceMountedAction('viewTask', ['task_id' => $arguments['task']]))
                ];
            });
    }

    public function deleteCommitNumber($taskId, $commit)
    {
        $task = Task::find($taskId);

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
            ->fillForm(function (array $arguments) {
                $record = Task::find($arguments['task_id']);

                return [
                    'start_date' => $record->start_date,
                    'due_date' => $record->due_date
                ];
            })
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
            ->action(function ($data, array $arguments): void {
                $task = Task::find($arguments['task_id']);

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
            ->extraModalFooterActions(function (array $arguments) {
                return [
                    Action::make('closeModal')
                        ->label(__('task.close'))
                        ->color('gray')
                        ->action(fn() => $this->replaceMountedAction('viewTask', ['task_id' => $arguments['task_id']]))
                ];
            });
    }

    public function toggleSortByPriority()
    {
        if ($this->sortBy == 'priority') {
            $this->sortBy = 'default';
            Cookie::queue('sort_by', 'default', 60 * 24 * 30);
        } else {
            $this->sortBy = 'priority';
            Cookie::queue('sort_by', 'priority', 60 * 24 * 30);
        }
    }
}
