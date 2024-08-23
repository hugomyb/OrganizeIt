<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Concerns\CanProcessDescription;
use App\Concerns\CanShowNotification;
use App\Concerns\InteractsWithTaskForm;
use App\Filament\Resources\ProjectResource;
use App\Jobs\SendEmailJob;
use App\Livewire\TaskRow;
use App\Livewire\TasksGroup;
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
use Filament\Resources\Pages\Concerns\CanAuthorizeResourceAccess;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Stichoza\GoogleTranslate\GoogleTranslate;

class ShowProject extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithTaskForm;
    use InteractsWithActions;
    use CanAuthorizeResourceAccess;
    use CanProcessDescription;
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
    public $groups;

    public $search;
    public $statusFilters;
    public $priorityFilters;
    public string $sortBy = 'default';

    public $toggleCompletedTasks;

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
                $query
                    ->where(function ($query) use ($statusIds, $priorityIds, $search) {
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

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getHeader(): ?View
    {
        return \view('vendor.filament.filament.resources.views.components.header.index', [
            'heading' => $this->getHeading(),
            'actions' => $this->getHeaderActions(),
        ]);
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
                ->form($this->getTaskForm($this->record))
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

    public function cancelCreateTask()
    {
        if (Storage::exists('tasks/' . Task::latest()->first()->id + 1)) {
            Storage::deleteDirectory('tasks/' . Task::latest()->first()->id + 1);
        }
    }

    public function updateTaskOrder($data)
    {
        // Restructurer les données pour organiser les groupes et les tâches correctement
        $structuredData = $this->restructureData($data);

        foreach ($structuredData as $groupData) {
            $groupId = $groupData['group_id'];
            $tasks = $groupData['tasks'];

            foreach ($tasks as $task) {
                $this->updateTaskAndChildren($task, $groupId);
            }
        }
    }

    private function restructureData($data)
    {
        $taskMap = [];
        $structuredData = [];

        // Construire un mappage de toutes les tâches
        foreach ($data as $item) {
            if (isset($item['value']) && strpos($item['value'], 'group-') === false) {
                $taskMap[$item['value']] = [
                    'value' => $item['value'],
                    'order' => $item['order'],
                    'parent_id' => null,
                    'items' => []
                ];
            }
        }

        // Associer les sous-tâches à leurs parents
        foreach ($data as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                foreach ($item['items'] as $subItem) {
                    if (isset($taskMap[$subItem['value']])) {
                        // Vérifier que parent_id est un entier valide
                        if (isset($taskMap[$item['value']])) {
                            $taskMap[$subItem['value']]['parent_id'] = $item['value'];
                            $taskMap[$item['value']]['items'][] = &$taskMap[$subItem['value']];
                        }
                    }
                }
            }
        }

        // Réassembler les groupes avec les tâches de premier niveau
        foreach ($data as $item) {
            if (isset($item['value']) && strpos($item['value'], 'group-') !== false) {
                $groupId = str_replace('group-', '', $item['value']);
                $groupTasks = [];
                if (isset($item['items'])) {
                    foreach ($item['items'] as $groupItem) {
                        if (isset($taskMap[$groupItem['value']])) {
                            $groupTasks[] = $taskMap[$groupItem['value']];
                        }
                    }
                }
                $structuredData[] = [
                    'group_id' => $groupId,
                    'tasks' => $groupTasks
                ];
            }
        }

        return $structuredData;
    }

    private function updateTaskAndChildren($task, $groupId)
    {
        $taskModel = Task::find($task['value']);
        if ($taskModel) {
            // Assurez-vous que parent_id est un entier valide ou null
            $taskModel->group_id = $groupId;
            $taskModel->parent_id = is_numeric($task['parent_id']) ? (int)$task['parent_id'] : null;
            $taskModel->order = $task['order'];
            $taskModel->save();

            // Traiter les sous-tâches récursivement
            if (isset($task['items']) && is_array($task['items'])) {
                foreach ($task['items'] as $childTask) {
                    $this->updateTaskAndChildren($childTask, $groupId);
                }
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
