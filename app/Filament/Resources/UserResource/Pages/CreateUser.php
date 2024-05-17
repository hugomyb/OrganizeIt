<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\PasswordResetNotification;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($data['name']) . "&background=random&size=128&bold=true&format=svg";

        // Télécharge l'image
        $avatarContents = file_get_contents($avatarUrl);

        // Génère un nom de fichier unique
        $filename = 'avatars/' . Str::uuid() . '.svg';

        // Sauvegarde l'image dans le stockage
        Storage::disk('public')->put($filename, $avatarContents);

        // Met à jour l'utilisateur avec le chemin de l'avatar
        $data['avatar'] = $filename;

        // password temporaire
        $data['password'] = bcrypt(Str::random());

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = parent::handleRecordCreation($data);

        $expiresAt = now()->addHours(12);

        // Envoie la notification de réinitialisation de mot de passe
        $user->sendWelcomeNotification($expiresAt);

        return $user;
    }
}
