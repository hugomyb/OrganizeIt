<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_welcome_email')
                ->label(__('general.actions.send_welcome_email'))
                ->action(function () {
                    $this->record->sendWelcomeNotification(validUntil: now()->addWeek());

                    Notification::make()
                        ->success()
                        ->title(__('general.notifications.welcome_email_sent'))
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!isset($data['avatar_url'])) {
            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($data['name']) . "&background=random&size=128&bold=true&format=svg";

            // Télécharge l'image
            $avatarContents = file_get_contents($avatarUrl);

            // Génère un nom de fichier unique
            $filename = 'avatars/' . Str::uuid() . '.svg';

            // Sauvegarde l'image dans le stockage
            Storage::disk('public')->put($filename, $avatarContents);

            // Met à jour l'utilisateur avec le chemin de l'avatar
            $data['avatar_url'] = $filename;
        }

        return $data;
    }
}
