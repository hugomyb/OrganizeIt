<?php

namespace App\Livewire;

use App\Models\Project;
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
            $this->results = Project::search($this->search)->get();
//            dd($this->results);
        }
    }

    public function render()
    {
        return view('livewire.global-searchbar');
    }
}
