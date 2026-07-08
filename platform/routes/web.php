<?php

use App\Http\Controllers\Auth\PasswordChangeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('password/change', [PasswordChangeController::class, 'show'])
    ->middleware(['auth'])
    ->name('password.change');

Route::post('password/change', [PasswordChangeController::class, 'store'])
    ->middleware(['auth'])
    ->name('password.change.store');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
