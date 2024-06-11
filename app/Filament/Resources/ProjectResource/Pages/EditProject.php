<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Mail\AssignToProjectMail;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function afterValidate(): void
    {
        $project = $this->record;
        $oldUsers = $project->users->pluck('id')->toArray();
        $newUsers = $this->data['users'];

        // envoyer un mail seulement aux utilisateurs ajoutés (pas aux utilisateurs retirés)
        $usersToAdd = array_diff($newUsers, $oldUsers);
        $author = auth()->user();

        foreach ($usersToAdd as $userId) {
            $user = User::find($userId);
            Mail::to($user)->send(new AssignToProjectMail($project, $author));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole('Admin');
    }
}
