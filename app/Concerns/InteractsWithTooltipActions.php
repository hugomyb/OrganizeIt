<?php

namespace App\Concerns;

use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithTooltipActions
{
    public function editTaskTooltipAction(): Action
    {
        return EditAction::make('editTask')
            ->modalWidth('5xl')
            ->iconButton()
            ->icon('heroicon-o-pencil')
            ->record(fn(array $arguments) => Task::find($arguments['task_id']))
            ->form($this->getTaskForm());
    }
}
