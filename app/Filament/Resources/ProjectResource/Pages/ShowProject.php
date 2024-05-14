<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Widgets\TasksTree;
use App\Models\Project;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ShowProject extends Page
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.show-project';

    public $record;

    public function mount($record)
    {
        $this->record = Project::find($record);
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return $this->record->name;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TasksTree::make([
                'record' => $this->record,
            ]),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
