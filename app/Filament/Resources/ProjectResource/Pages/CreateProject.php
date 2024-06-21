<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Mail\AssignToProjectMail;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function afterCreate(): void
    {
        $project = $this->record;
        $users = $project->users;
        $author = auth()->user();

        foreach ($users as $user) {
            Mail::to($user)->send(new AssignToProjectMail($project, $author));
        }
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    protected function getRedirectUrl(): string
    {
        return ProjectResource::getUrl('show', ['record' => $this->record->id]);
    }
}
