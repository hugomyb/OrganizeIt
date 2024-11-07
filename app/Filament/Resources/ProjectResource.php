<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('project.project');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make(__('project.project'))
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('project.form.name'))
                            ->required(),

                        Forms\Components\ColorPicker::make('color')
                            ->label(__('project.form.color'))
                            ->unique(ignoreRecord: true)
                            ->suffixAction(Forms\Components\Actions\Action::make('randomize')
                                ->label(__('project.form.color.randomize'))
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn($set) => $set('color', '#' . bin2hex(random_bytes(3))))
                            )
                            ->default('#000000'),
                    ]),

                Forms\Components\Fieldset::make(__('project.form.users.users'))
                    ->columns(1)
                    ->schema([
                        Forms\Components\Select::make('users')
                            ->label(__('project.form.users.assigns'))
                            ->preload()
                            ->searchable()
                            ->default([
                                auth()->id(),
                            ])
                            ->relationship('users', 'name')
                            ->multiple(),
                    ]),
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
                Tables\Columns\ColorColumn::make('color')
                    ->label(__('project.table.color'))
                    ->width('50px'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('project.table.name'))
                    ->searchable()
                    ->width('200px')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('users.avatar_url')
                    ->label(__('project.table.users.assigns'))
                    ->visible(!auth()->user()->hasRole('Client'))
                    ->circular()
                    ->tooltip(fn($record) => $record->users->map(fn($user) => $user->name)->join(', '))
                    ->stacked()
                    ->size(30),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('project.table.status'))
                    ->badge()
                    ->width('50px')
                    ->default('')
                    ->toggleable()
                    ->formatStateUsing(fn($record) => $record->deleted_at ? __('project.table.archived') : __('project.table.active'))
                    ->color(fn($state) => $state ? 'danger' : 'success')
                    ->visible(auth()->user()->hasRole('Admin'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordUrl(fn($record) => !$record->deleted_at ? ProjectResource::getUrl('show', ['record' => $record]) : null)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(auth()->user()->hasRole('Admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(auth()->user()->hasRole('Admin')),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'show' => Pages\ShowProject::route('/{record}/tasks'),
        ];
    }
}
