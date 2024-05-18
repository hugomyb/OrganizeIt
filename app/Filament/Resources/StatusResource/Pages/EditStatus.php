<?php

namespace App\Filament\Resources\StatusResource\Pages;

use App\Filament\Resources\StatusResource;
use App\Models\Status;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatus extends EditRecord
{
    protected static string $resource = StatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        if (!auth()->user()->hasRole('Admin')) {
            return false;
        }

        $status = Status::find($parameters['record'])->first();

        return $status->name !== 'À faire' && $status->name !== 'En cours' && $status->name !== 'Terminé';
    }
}
