<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\Auth\PasswordChangeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('password/change', [PasswordChangeController::class, 'show'])
    ->middleware(['auth'])
    ->name('password.change');

Route::post('password/change', [PasswordChangeController::class, 'store'])
    ->middleware(['auth'])
    ->name('password.change.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    Route::resource('artists', ArtistController::class);
});

require __DIR__.'/auth.php';
