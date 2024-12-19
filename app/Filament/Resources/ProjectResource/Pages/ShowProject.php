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
use App\Mail\NewCommitMail;
use App\Mail\NewTaskMail;
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
use Filament\Actions\StaticAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\CanAuthorizeResourceAccess;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

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

    public $groups = [];
    public $loadedGroupCount = 0;
    public $groupLoadLimit = 1;

    public $search;
    public ?array $results = [];

    public $statusFilters;
    public $priorityFilters;
    public string $sortBy = 'default';

    public $toggleCompletedTasks;

    public bool $shouldReloadGroups = true;

    public function render(): \Illuminate\Contracts\View\View
    {
        if ($this->shouldReloadGroups) {
            $this->loadGroups();
            $this->shouldReloadGroups = false;
        }
        return parent::render();
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::ScreenTwoExtraLarge;
    }

    public function mount($record): void
    {
        $this->record = Project::with(['users', 'groups'])->find($record);
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

    public function loadGroups($append = false): void
    {
        $statusIds = $this->statusFilters->pluck('id')->toArray();
        $priorityIds = $this->priorityFilters->pluck('id')->toArray();
        $sortBy = $this->sortBy;
        $search = $this->search;

        $newGroups = Group::with(['tasks' => function ($query) use ($statusIds, $priorityIds, $sortBy, $search) {
            $query->where(function ($query) use ($statusIds, $priorityIds, $search) {
                if (!empty($statusIds)) {
                    $query->whereIn('status_id', $statusIds);
                }
                if (!empty($priorityIds)) {
                    $query->whereIn('priority_id', $priorityIds);
                }
                if (!empty($search)) {
                    $query->where('title', 'like', '%' . $search . '%');
                }
            });

            if ($sortBy === 'priority') {
                $query->orderBy('priority_id', 'desc');
            } else {
                $query->orderBy('order', 'asc');
            }
        }])
            ->where('project_id', $this->record->id)
            ->skip($this->loadedGroupCount) // Sauter les groupes déjà chargés
            ->take($this->groupLoadLimit)   // Charger un nombre limité de groupes
            ->get();

        if ($append) {
            $this->groups = $this->groups->merge($newGroups);
        } else {
            $this->groups = $newGroups;
        }

        $this->loadedGroupCount += $newGroups->count();

        foreach ($newGroups as $group) {
            $this->dispatch('reloadTasks:' . $group->id, $this->statusFilters->toArray(), $this->priorityFilters->toArray(), $this->search, $this->sortBy)->to(TasksGroup::class);
        }
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
            CreateAction::make('headerCreateTask')
                ->visible(auth()->user()->hasPermission('manage_tasks'))
                ->modalWidth('7xl')
                ->model(Task::class)
                ->label(__('task.add_task'))
                ->closeModalByClickingAway(false)
                ->icon('heroicon-o-plus')
                ->modalHeading(__('task.add_task'))
                ->form($this->getTaskForm($this->record))
                ->modalSubmitActionLabel(__('task.add'))
                ->createAnother()
                ->modalCancelAction(fn(StaticAction $action, $data) => $action->action('cancelCreateTask'))
                ->action(function (array $data, CreateAction $action, $form, array $arguments): void {
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

                    if ($arguments['another'] ?? false) {
                        $action->callAfter();

                        $action->record(null);

                        $form->model(Task::class);

                        $form->fill();

                        $action->halt();
                    }
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

        foreach ($this->groups as $group) {
            $this->dispatch('reloadTasks:' . $group->id, $this->statusFilters->toArray(), $this->priorityFilters->toArray(), $this->search, $this->sortBy)->to(TasksGroup::class);
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

        foreach ($this->groups as $group) {
            $this->dispatch('reloadTasks:' . $group->id, $this->statusFilters->toArray(), $this->priorityFilters->toArray(), $this->search, $this->sortBy)->to(TasksGroup::class);
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
            $this->sortBy = 'default';
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

        foreach ($this->groups as $group) {
            $this->dispatch('reloadTasks:' . $group->id, $this->statusFilters->toArray(), $this->priorityFilters->toArray(), $this->search, $this->sortBy)->to(TasksGroup::class);
        }
    }

    public function addUserToProject($userId)
    {
        $this->record->users()->attach($userId);

        $user = User::find($userId);

        SendEmailJob::dispatch(AssignToProjectMail::class, $user, $this->record, auth()->user());

        $this->record = $this->record->fresh('users');

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

        $this->dispatch('refreshedGroup', $this->sortBy)->to(TaskRow::class);

        foreach ($this->groups as $group) {
            $this->dispatch('reloadTasks:' . $group->id, $this->statusFilters->toArray(), $this->priorityFilters->toArray(), $this->search, $this->sortBy)->to(TasksGroup::class);
        }
    }

    #[On('openTask')]
    public function openTaskAction($taskId)
    {
        $this->mountAction('viewTask', ['task_id' => $taskId]);
    }

    public function viewTaskAction(): Action
    {
        return ViewAction::make('viewTask')
            ->modalHeading('')
            ->modal()
            ->slideOver()
            ->modalWidth('6xl')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->modalContent(fn($record, array $arguments) => view('filament.resources.project-resource.widgets.view-task', ['task' => $record]))
            ->modalFooterActions(function (array $arguments, ViewAction $action, $livewire, $record) {
                return [
                    $action->getModalCancelAction()
                        ->alpineClickHandler("() => { \$dispatch('modal-closed:{$record->id}'); \$dispatch('close-modal', { id: '{$livewire->getId()}-action' }); }"),
                    Action::make('updated_at')
                        ->link()
                        ->disabled()
                        ->label(__('general.last_updated_at') . ' ' . Task::find($arguments['task_id'])->updated_at->translatedFormat('d M Y - H:i'))
                        ->color('gray')
                ];
            });
    }

    #[On('openCommitModal')]
    public function openCommitAction($taskId)
    {
        $this->replaceMountedAction('addCommit', ['task_id' => $taskId]);
    }

    public function addCommitAction(): Action
    {
        return Action::make('addCommit')
            ->hiddenLabel()
            ->modal()
            ->icon('heroicon-o-plus')
            ->tooltip(__('task.add_commit_number'))
            ->modalHeading(__('task.add_commit'))
            ->form([
                TextInput::make('commitNumber')
                    ->label(__('task.number'))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments, Action $action): void {
                $task = Task::find($arguments['task_id']);

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
                } else {
                    Notification::make()
                        ->danger()
                        ->title(__('task.commit.error'))
                        ->send();

                    $action->halt();
                }
            })
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false)
            ->extraModalFooterActions(function (array $arguments) {
                return [
                    Action::make('closeModal')
                        ->label(__('general.cancel'))
                        ->color('gray')
                        ->action(fn() => $this->replaceMountedAction('viewTask', ['task_id' => $arguments['task_id']]))
                ];
            });
    }

    #[On('openDatesModal')]
    public function openDatesAction($taskId)
    {
        $this->replaceMountedAction('updateDates', ['task_id' => $taskId]);
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
                        ->label(__('general.cancel'))
                        ->color('gray')
                        ->action(fn() => $this->replaceMountedAction('viewTask', ['task_id' => $arguments['task_id']]))
                ];
            });
    }

    #[On('openTaskById')]
    public function openTaskById($taskId)
    {
        if (Task::find($taskId)) {
            $this->mountAction('viewTask', ['task_id' => $taskId]);
        }
    }

    public function updatedSearch(): void
    {
        $this->record->load('groups.tasks');

        $this->results = $this->record->groups->map(function ($group) {
            return [
                'group' => $group,
                'tasks' => $group->tasks->filter(function ($task) {
                    return stripos($task->title, $this->search) !== false;
                })
            ];
        })->filter(function ($group) {
            return $group['tasks']->isNotEmpty();
        })->toArray();
    }
}
