<?php

namespace App\Http\Controllers\Auth;

use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Spatie\WelcomeNotification\WelcomeController as BaseWelcomeController;

class WelcomeController extends BaseWelcomeController
{
    public function sendPasswordSavedResponse(): Response
    {
        return redirect(Filament::getLoginUrl());
    }

    public function showWelcomeForm(Request $request, User $user)
    {
        return view('livewire.welcome-init-password-page')->with([
            'email' => $request->email,
            'user' => $user,
        ]);
    }
}
