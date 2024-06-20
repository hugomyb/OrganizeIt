<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                            ->suffixAction(A)
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
                    ->circular()
                    ->tooltip(fn($record) => $record->users->map(fn($user) => $user->name)->join(', '))
                    ->stacked()
                    ->size(30),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn($record) => ProjectResource::getUrl('show', ['record' => $record]))
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
