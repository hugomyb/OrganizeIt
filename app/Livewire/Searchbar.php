<?php

namespace App\Livewire;

use App\Filament\Resources\ProjectResource\Pages\ShowProject;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResults;
use Livewire\Component;

class Searchbar extends Component
{
    public ?Project $project = null;

    public ?string $search = '';

    public function mount(): void
    {
        $this->project = $this->getProjectInstance();
    }

    public function getResults(): ?GlobalSearchResults
    {
        $search = trim($this->search);

        if (blank($search)) {
            return null;
        }

        $results = GlobalSearchResults::make();

        $this->project->load('groups.tasks');

        $tasks = $this->project->groups->map(function ($group) {
            return [
                'group' => $group,
                'tasks' => $group->tasks->filter(function ($task) {
                    $search = $this->search;

                    $matchTitle = stripos($task->title, $search) !== false;

                    $matchId = (string) $task->id === $search;

                    $matchCommit = collect($task->commit_numbers)
                        ->contains(fn ($commit) => stripos((string) $commit, $search) !== false);

                    return $matchTitle || $matchId || $matchCommit;
                }),
            ];
        })->filter(function ($group) {
            return $group['tasks']->isNotEmpty();
        })->toArray();

        foreach ($tasks as $task) {
            $results->category($task['group']->name, $task['tasks']->toArray());
        }

        $this->dispatch('open-global-search-results');

        return $results;
    }

    public function getProjectInstance () {
        $record = request()->route('record');

        if ($record == null) {
            return null;
        } else {
            return Project::find($record);
        }
    }

    public function openTask($id): void
    {
        $this->dispatch('openTaskById', $id)->to(ShowProject::class);
    }

    public function render()
    {
        return view('livewire.searchbar', [
            'results' => $this->getResults(),
        ]);
    }
}
