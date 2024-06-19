<?php

namespace App\Filament\Pages;

use App\Concerns\HasUser;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class EditProfilePage extends Page implements HasForms
{
    use InteractsWithForms, HasUser;

    protected static string $view = 'filament.pages.edit-profile-page';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $profileData = [];
    public ?array $passwordData = [];

    public $userClass;

    public function mount()
    {
        $this->user = $this->getUser();
        $this->userClass = get_class($this->user);

        $this->profileForm->fill($this->user->only('avatar_url', 'name', 'email'));
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return __('profile.heading');
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('profile.info'))
                    ->aside()
                    ->description(__('profile.update_profile_info'))
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->default('https://ui-avatars.com/api/?name=' . urlencode($this->user->name) . '&background=random&size=128&bold=true&format=svg')
                            ->label('Avatar')
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars'),
                        TextInput::make('name')
                            ->label(__('profile.name'))
                            ->required(),
                        TextInput::make('email')
                            ->label(__('profile.email'))
                            ->email()
                            ->required()
                            ->unique($this->userClass, ignorable: $this->user),
                    ]),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('profile.password'))
                    ->aside()
                    ->description(__('profile.update_password'))
                    ->schema([
                        TextInput::make('Current password')
                            ->label(__('profile.current_password'))
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->revealable(),
                        TextInput::make('password')
                            ->label(__('profile.new_password'))
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation')
                            ->revealable(),
                        TextInput::make('passwordConfirmation')
                            ->label(__('profile.confirm_password'))
                            ->password()
                            ->required()
                            ->dehydrated(false)
                            ->revealable(),
                    ]),
            ])
            ->model($this->getUser())
            ->statePath('passwordData');
    }

    public function updateProfile(): void
    {
        try {
            $data = $this->profileForm->getState();

            if (!isset($data['avatar_url'])) {
                $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($data['name']) . "&background=random&size=128&bold=true&format=svg";

                $avatarContents = file_get_contents($avatarUrl);

                $filename = 'avatars/' . Str::uuid() . '.svg';

                Storage::disk('public')->put($filename, $avatarContents);

                $data['avatar_url'] = $filename;
            }

            $this->user->update($data);
        } catch (Halt $exception) {
            return;
        }

        Notification::make()
            ->success()
            ->title(__('profile.profile_updated'))
            ->send();
    }

    public function updatePassword(): void
    {
        try {
            $data = $this->passwordForm->getState();

            $newData = [
                'password' => $data['password'],
            ];

            $this->user->update($newData);
        } catch (Halt $exception) {
            return;
        }

        if (request()->hasSession() && array_key_exists('password', $data)) {
            request()->session()->put([
                'password_hash_' . Filament::getAuthGuard() => $data['password'],
            ]);
        }

        $this->passwordForm->fill();

        Notification::make()
            ->success()
            ->title(__('profile.password_updated'))
            ->send();
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm'
        ];
    }
}
