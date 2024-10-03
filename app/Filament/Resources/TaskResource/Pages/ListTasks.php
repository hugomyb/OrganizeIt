<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;
    protected ?string $maxContentWidth = 'full';

    protected string $pageTitle;

    public function mount(): void
    {
        $this->pageTitle = __('task.my_tasks');
        $this->activeTab = 0;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->pageTitle;
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();
    }

    protected function applyFiltersToTableQuery(Builder $query): Builder
    {
        if ((int)$this->activeTab === 1) {
            $this->pageTitle = __('task.unassigned_tasks');
        } else {
            if (auth()->user()->hasRole('Admin')) {
                $usersFilterState = $this->getTableFilterState('users.id')['values'];
                if (count($usersFilterState) > 0) {
                    if (count($usersFilterState) === 1 && in_array(auth()->id(), $usersFilterState)) {
                        $this->pageTitle = __('task.my_tasks');
                    } else {
                        $users = \App\Models\User::whereIn('id', $usersFilterState)->get();

                        $lastUser = $users->pop();
                        $this->pageTitle = __('task.assigned_tasks_to') . ' ' . $users->implode('name', ', ') . ' & ' . $lastUser->name;
                    }
                } else {
                    $this->pageTitle = 'Toutes les tâches assignées';
                }
            } else {
                $this->pageTitle = __('task.my_tasks');
            }
        }

        return parent::applyFiltersToTableQuery($query);
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make('Assignés')
                ->modifyQueryUsing(function ($query) {
                    if (auth()->user()->hasRole('Admin')) {
                        $query->whereHas('users');
                    } else {
                        $query->whereHas('users', function ($query) {
                            $query->where('user_id', auth()->id());
                        });
                    }
                }),

            Tab::make('Non assignées')
                ->modifyQueryUsing(function ($query) {
                    $query->whereDoesntHave('users')
                        ->whereHas('project.users', function ($q) {
                            $q->where('users.id', auth()->id());
                        });
                })
        ];
    }
}
