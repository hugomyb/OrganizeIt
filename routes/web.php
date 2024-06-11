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

Route::get('/mailable', function () {

    $task = App\Models\Task::find(8);
    $comment = App\Models\Comment::first();

    return new \App\Mail\NewCommentMail($task, $comment);
});
