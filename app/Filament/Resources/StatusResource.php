<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StatusResource\Pages;
use App\Filament\Resources\StatusResource\RelationManagers;
use App\Models\Status;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Stichoza\GoogleTranslate\GoogleTranslate;

class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static ?string $navigationIcon = 'pepicon-hourglass-circle';

    public static function getModelLabel(): string
    {
        return __('status.status');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('general.admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->suffixAction(Action::make('randomize')
                        ->label(__('project.form.color.randomize'))
                        ->icon('heroicon-o-arrow-path')
                        ->action(fn($set) => $set('color', '#' . bin2hex(random_bytes(3))))
                    )
                    ->unique(ignoreRecord: true)
                    ->label(__('status.table.color'))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('status.table.name')),

                Tables\Columns\ColorColumn::make('color')
                    ->label(__('status.table.color')),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn($record) => $record->id !== Status::whereName('À faire')->first()->id && $record->id !== Status::whereName('En cours')->first()->id && $record->id !== Status::getCompletedStatusId() ? StatusResource::getUrl('edit', ['record' => $record]) : null)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->id !== Status::whereName('À faire')->first()->id && $record->id !== Status::whereName('En cours')->first()->id && $record->id !== Status::getCompletedStatusId()),
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
