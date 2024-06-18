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
        return __('general.init_mdp');
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
                    ->label(__('general.mdp'))
                    ->required()
                    ->revealable()
                    ->password(),

                TextInput::make('password_confirmation')
                    ->statePath('data.password_confirmation')
                    ->required()
                    ->label(__('general.mdp_confirmation'))
                    ->revealable()
                    ->password(),
            ]);
    }

    public function apply()
    {
        $this->validate([
            'data.password' => 'required|confirmed|min:8',
        ], [
            'data.password.required' => __('general.mdp_validation.required'),
            'data.password.confirmed' => __('general.mdp_validation.confirmed'),
            'data.password.min' => __('general.mdp_validation.min'),
        ]);

        $this->user->forceFill([
            'password' => Hash::make($this->data['password']),
            'welcome_valid_until' => null,
            'email_verified_at' => now(),
        ]);

        $this->user->save();

        redirect(Filament::getHomeUrl());
    }
}
