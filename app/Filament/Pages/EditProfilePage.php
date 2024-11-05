<?php

namespace App\Filament\Pages;

use App\Concerns\HasUser;
use App\Mail\AssignToProjectMail;
use App\Mail\AssignToTaskMail;
use App\Mail\ChangeTaskPriorityMail;
use App\Mail\ChangeTaskStatusMail;
use App\Mail\NewCommentMail;
use App\Mail\NewCommitMail;
use App\Mail\NewTaskMail;
use App\Models\Status;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
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
    public ?array $settingsData = [];

    public $userClass;

    public function mount()
    {
        $this->user = $this->getUser();
        $this->userClass = get_class($this->user);

        $this->profileForm->fill($this->user->only('avatar_url', 'name', 'email'));

        if (!auth()->user()->hasRole('Client')) {
            $settings = $this->user->settings()->where('key', 'notifications')->first();

            if ($settings) {
                $this->settingsData = [
                    'notifications' => json_decode($settings->value, true)
                ];
            } else {
                $defaultNotifications = [];

                foreach ($this->getMailClasses() as $mailClass => $mail) {
                    $notificationKey = class_basename($mailClass);
                    $defaultNotifications[$notificationKey] = ['enabled' => true];

                    if ($notificationKey === 'ChangeTaskStatusMail') {
                        $defaultNotifications[$notificationKey]['statuses'] = array_keys($this->getStatuses());
                    }
                }

                $this->user->settings()->create([
                    'key' => 'notifications',
                    'value' => json_encode($defaultNotifications)
                ]);

                $this->settingsData = ['notifications' => $defaultNotifications];
            }
        }
    }

    /**
     * @return string|Htmlable
     */
    public function getHeading(): string|Htmlable
    {
        return __('profile.heading');
    }

    public function getMailClasses(): array
    {
        return [
            AssignToProjectMail::class => [
                'label' => __('profile.emails.assign_to_project'),
                'description' => __('profile.emails.assign_to_project_description'),
            ],
            AssignToTaskMail::class => [
                'label' => __('profile.emails.assign_to_task'),
                'description' => __('profile.emails.assign_to_task_description'),
            ],
            ChangeTaskPriorityMail::class => [
                'label' => __('profile.emails.change_task_priority'),
                'description' => __('profile.emails.change_task_priority_description'),
            ],
            ChangeTaskStatusMail::class => [
                'label' => __('profile.emails.change_task_status'),
                'description' => __('profile.emails.change_task_status_description'),
            ],
            NewCommentMail::class => [
                'label' => __('profile.emails.new_comment'),
                'description' => __('profile.emails.new_comment_description'),
            ],
            NewCommitMail::class => [
                'label' => __('profile.emails.new_commit'),
                'description' => __('profile.emails.new_commit_description'),
            ],
            NewTaskMail::class => [
                'label' => __('profile.emails.new_task'),
                'description' => __('profile.emails.new_task_description'),
            ]
        ];
    }

    public function getStatuses(): array
    {
        return Status::all()->pluck('name', 'id')->toArray();
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
                            ->dehydrateStateUsing(fn($state): string => Hash::make($state))
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

    public function settingsForm(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make(__('profile.email_notifications'))
                            ->extraAttributes(['class' => '!p-0'])
                            ->schema([
                                View::make('components.settings.notification-table')
                                    ->viewData([
                                        'notificationTypes' => $this->getMailClasses(),
                                        'userSettings' => $this->settingsData,
                                        'statuses' => $this->getStatuses(),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('settingsData');
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

    public function toggleNotificationSetting($notificationType, $enabled)
    {
        $settings = $this->user->settings()->where('key', 'notifications')->first();
        $currentSettings = json_decode($settings->value, true) ?? [];

        $currentSettings[$notificationType] = ['enabled' => $enabled];

        $settings->update(['value' => json_encode($currentSettings)]);

        $this->settingsData['notifications'] = $currentSettings;

        Notification::make()
            ->success()
            ->title(__('profile.notification_settings_updated'))
            ->send();
    }

    public function toggleStatusNotification($notificationType, $statusId, $enabled)
    {
        $settings = $this->user->settings()->where('key', 'notifications')->first();
        $currentSettings = json_decode($settings->value, true) ?? [];

        if (!isset($currentSettings[$notificationType]['statuses'])) {
            $currentSettings[$notificationType]['statuses'] = [];
        }

        if ($enabled) {
            $currentSettings[$notificationType]['statuses'][] = $statusId;
            $currentSettings[$notificationType]['statuses'] = array_unique($currentSettings[$notificationType]['statuses']);
        } else {
            $currentSettings[$notificationType]['statuses'] = array_diff($currentSettings[$notificationType]['statuses'], [$statusId]);
        }

        if (!empty($currentSettings[$notificationType]['statuses'])) {
            $currentSettings[$notificationType]['enabled'] = true;
        } else {
            $currentSettings[$notificationType]['enabled'] = false;
        }

        $settings->update(['value' => json_encode($currentSettings)]);

        $this->settingsData['notifications'] = $currentSettings;

        Notification::make()
            ->success()
            ->title(__('profile.notification_settings_updated'))
            ->send();
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm',
            'settingsForm',
        ];
    }
}
