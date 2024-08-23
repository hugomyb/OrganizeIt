<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;
    protected ?string $maxContentWidth = 'full';

    public function getTitle(): string|Htmlable
    {
        return __('task.my_tasks');
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
