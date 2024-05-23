<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $modelLabel = 'Role';

    protected static ?string $navigationGroup = 'Administrateur';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->unique(ignoreRecord: true),

                Forms\Components\Select::make('permissions')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('permissions', 'name')
                    ->label('Permissions'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom'),

                Tables\Columns\TextColumn::make('permissions')
                    ->html()
                    ->formatStateUsing(function ($state) {
                        return '<span title="' . $state->description .'">' . $state->name . '</span>';
                    })
                    ->badge()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
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

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
