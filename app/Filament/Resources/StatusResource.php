<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatusResource\Pages;
use App\Filament\Resources\StatusResource\RelationManagers;
use App\Models\Status;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static ?string $navigationIcon = 'pepicon-hourglass-circle';

    protected static ?string $navigationGroup = 'Administrateur';

    protected static ?string $modelLabel = 'Statut';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->unique('statuses', ignoreRecord: true)
                    ->label('Nom')
                    ->required(),

                ColorPicker::make('color')
                    ->label('Couleur')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Couleur'),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn($record) => $record->name !== 'À faire' && $record->name !== 'En cours' && $record->name !== 'Terminé' ? StatusResource::getUrl('edit', ['record' => $record]) : null)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->name !== 'À faire' && $record->name !== 'En cours' && $record->name !== 'Terminé'),
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
            'index' => Pages\ListStatuses::route('/'),
            'create' => Pages\CreateStatus::route('/create'),
            'edit' => Pages\EditStatus::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
