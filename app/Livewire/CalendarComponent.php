<?php

namespace App\Livewire;

use Livewire\Component;

class CalendarComponent extends Component
{
    public $events = [];

    public function mount()
    {
        $this->loadEvents();
    }

    public function loadEvents()
    {
        $this->events = [
            [
                'id' => '1',
                'title' => 'Tâche 1',
                'start' => '2024-11-06T10:00:00',
                'end' => '2024-11-06T12:00:00',
                'category' => 'time'
            ]
        ];
    }

    public function render()
    {
        return view('livewire.calendar-component');
    }
}
