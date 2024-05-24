<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';


    /**
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('user.user');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('general.admin');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role->name === 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('user.form.name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('role_id')
                    ->label(__('user.form.role'))
                    ->default(Role::whereName('Utilisateur')->first()->id)
                    ->options(fn() => \App\Models\Role::pluck('name', 'id')->toArray())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->size(30),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('user.table.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->label(__('user.form.role'))
                    ->searchable()
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
