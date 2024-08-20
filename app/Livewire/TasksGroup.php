<?php

namespace App\Livewire;

use App\Models\Group;
use Illuminate\Support\Collection;
use Livewire\Component;

class TasksGroup extends Component
{
    public Group $group;
    public Collection $tasks;

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->tasks = $group->tasks->sortBy('order');
    }

    public function render()
    {
        return view('livewire.tasks-group');
    }
}
