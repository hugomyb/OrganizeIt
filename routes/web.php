<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// password reset
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
