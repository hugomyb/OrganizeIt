<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Status;
use App\Models\Task;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// My Assigned Tasks
class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';

    protected static bool $shouldRegisterNavigation = true;

    public static function getLabel(): ?string
    {
        return __('task.my_tasks');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->whereHas('users', function ($query) {
                    $query->where('user_id', auth()->id());
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->searchable(),

                Tables\Columns\ColorColumn::make('project.color')
                    ->label(__('task.form.project'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('task.form.title'))
                    ->limit(70)
                    ->searchable(),

                Tables\Columns\TextColumn::make('priority.name')
                    ->label(__('task.form.priority'))
                    ->icon('iconsax-bol-flag-2')
                    ->badge()
                    ->color(function ($record) {
                        return Color::hex($record->priority->color);
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label(__('task.form.status'))
                    ->badge()
                    ->icon(function ($record) {
                        switch ($record->status->name) {
                            case('À faire'):
                                return 'pepicon-hourglass-circle';
                                break;
                            case('En cours'):
                                return 'carbon-in-progress';
                                break;
                            case('Terminée'):
                                return 'grommet-status-good';
                                break;
                            default:
                                return 'pepicon-hourglass-circle';
                        }
                    })
                    ->color(function ($record) {
                        return Color::hex($record->status->color);
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('task.form.created_at'))
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
            ])
            ->recordUrl(fn ($record) => ProjectResource::getUrl('show', ['record' => $record->project]) . '?task=' . $record->id)
            ->persistFiltersInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('status.status'))
                    ->preload()
                    ->relationship(
                        'status',
                        fn () => app()->getLocale() === 'en' ? 'en_name' : 'name',
                        fn (Builder $query) => $query->orderBy('name'))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label(__('task.form.priority'))
                    ->preload()
                    ->relationship(
                        'priority',
                        fn () => app()->getLocale() === 'en' ? 'en_name' : 'name',
                        fn (Builder $query) => $query->orderBy('name'))
                    ->multiple(),
            ])
            ->defaultSort('project.color', 'asc')
            ->defaultGroup('project.name')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })->count();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
