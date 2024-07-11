<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GlobalSearchbar extends Component
{
    public $search = '';
    public $results = [];

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {
            $this->results = [];
            return;
        } else {
            $userId = Auth::id();

            $projectIds = Project::search($this->search)->get()->pluck('id');

            $this->results = Project::whereIn('id', $projectIds)
                ->whereHas('users', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->get();
        }
    }

    public function render()
    {
        return view('livewire.global-searchbar');
    }
}
