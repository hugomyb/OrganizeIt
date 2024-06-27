<?php

use App\Http\Controllers\Auth\WelcomeController;
use App\Livewire\WelcomeInitPasswordPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\WelcomeNotification\WelcomesNewUsers;

// password reset
Route::group(['middleware' => ['web', WelcomesNewUsers::class,]], function () {
    Route::get('welcome/{user}', WelcomeInitPasswordPage::class)->name('welcome');
    Route::post('welcome/{user}', [WelcomeController::class, 'savePassword']);
});
