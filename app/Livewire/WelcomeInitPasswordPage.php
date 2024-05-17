<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;

class WelcomeInitPasswordPage extends SimplePage implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.welcome-init-password-page';

    public $email;
    public $user;

    public ?array $data = [];

    public function getTitle(): string|Htmlable
    {
        return "Initialiser votre mot de passe";
    }

    public function mount(User $user)
    {
        $this->email = $user->email;
        $this->user = $user;
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return "Initialiser votre mot de passe";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label("Email")
                    ->disabled(),

                TextInput::make('password')
                    ->statePath('data.password')
                    ->label("Mot de passe")
                    ->required()
                    ->revealable()
                    ->password(),

                TextInput::make('password_confirmation')
                    ->statePath('data.password_confirmation')
                    ->required()
                    ->label("Confirmer le mot de passe")
                    ->revealable()
                    ->password(),
            ]);
    }

    public function apply()
    {
        $this->validate([
            'data.password' => 'required|confirmed|min:8',
        ], [
            'data.password.required' => "Le mot de passe est requis",
            'data.password.confirmed' => "Les mots de passe ne correspondent pas",
            'data.password.min' => "Le mot de passe doit contenir au moins 8 caractères",
        ]);

        $this->user->forceFill([
            'password' => Hash::make($this->data['password']),
            'welcome_valid_until' => null
        ]);

        $this->user->save();

        redirect(Filament::getHomeUrl());
    }
}
