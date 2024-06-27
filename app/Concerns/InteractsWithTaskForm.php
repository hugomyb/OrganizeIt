<?php

namespace App\Concerns;

use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Stichoza\GoogleTranslate\GoogleTranslate;

trait InteractsWithTaskForm
{
    public function getTaskForm(Project $project, $groupId = null): array
    {
        $statusOptions = Status::all()->mapWithKeys(function ($status) {
            $iconHtml = view('components.status-icon', ['status' => $status])->render();
            return [$status->id => $iconHtml];
        })->toArray();

        $priorityOptions = Priority::all()->mapWithKeys(function ($priority) {
            $iconHtml = view('components.priority-icon', ['priority' => $priority])->render();
            return [$priority->id => $iconHtml];
        })->toArray();

        return [
            Select::make('group_id')
                ->preload()
                ->searchable()
                ->label(__('group.group'))
                ->default($groupId ?? $project->groups->first()->id ?? null)
                ->required()
                ->options($project->groups->pluck('name', 'id')),

            TextInput::make('title')
                ->autofocus()
                ->label(__('task.form.title'))
                ->columnSpanFull()
                ->live()
                ->required(),

            RichEditor::make('description')
                ->columnSpanFull()
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory(fn($record) => $record ? 'tasks/' . $record->id . '/files' : 'tasks/' . Task::latest()->first()->id + 1 . '/files')
                ->label('Description'),

            \Filament\Forms\Components\Group::make([
                Select::make('status_id')
                    ->label(__('task.form.status'))
                    ->default(Status::whereName('À faire')->first()->id)
                    ->preload()
                    ->searchable()
                    ->options($statusOptions)
                    ->allowHtml()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->live(onBlur: true)
                            ->unique('statuses', ignoreRecord: true)
                            ->label(__('status.table.name'))
                            ->afterStateUpdated(function (Set $set, $state) {
                                $translate = app()->make(GoogleTranslate::class);
                                $translate->setSource('fr');
                                $translate->setTarget('en');
                                $result = $translate->translate($state ?? "");

                                $set('en_name', $result);
                            })
                            ->required(),

                        TextInput::make('en_name')
                            ->live(onBlur: true)
                            ->unique('statuses', ignoreRecord: true)
                            ->label(__('status.table.en_name'))
                            ->required(),

                        ColorPicker::make('color')
                            ->suffixAction(\Filament\Forms\Components\Actions\Action::make('randomize')
                                ->label(__('project.form.color.randomize'))
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn($set) => $set('color', '#' . bin2hex(random_bytes(3))))
                            )
                            ->unique(ignoreRecord: true)
                            ->label(__('status.table.color'))
                    ])->createOptionUsing(fn(array $data) => Status::create($data)->getKey())
                    ->required(),

                Select::make('priority_id')
                    ->label(__('task.form.priority'))
                    ->preload()
                    ->searchable()
                    ->default(Priority::whereName('Aucune')->first()->id)
                    ->disabled(fn() => auth()->user()->hasPermission('change_priority') ? false : true)
                    ->dehydrated()
                    ->options($priorityOptions)
                    ->allowHtml()
                    ->required(),
            ])->columns(2),

            Select::make('users')
                ->relationship(
                    name: 'users',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $project->users()->getQuery()
                )
                ->label(__('task.assign_to'))
                ->dehydrated()
                ->disabled(fn() => !auth()->user()->hasPermission('assign_user'))
                ->visible(fn() => auth()->user()->hasPermission('view_assigned_users'))
                ->preload()
                ->searchable()
                ->multiple(),

            \Filament\Forms\Components\Group::make([
                DatePicker::make('start_date')
                    ->label(__('task.start_date')),

                DatePicker::make('due_date')
                    ->label(__('task.end_date')),
            ])->visible(fn() => auth()->user()->hasPermission('manage_dates') ? true : false)
                ->columns(2),

            FileUpload::make('attachments')
                ->columnSpanFull()
                ->multiple()
                ->previewable()
                ->downloadable()
                ->multiple()
                ->appendFiles()
                ->preserveFilenames()
                ->reorderable()
                ->visibility('private')
                ->openable()
                ->directory(fn($record) => $record ? 'tasks/' . $record->id . '/attachments' : 'tasks/' . Task::latest()->first()->id + 1 . '/attachments')
                ->label(__('task.form.attachments'))
        ];
    }
}
