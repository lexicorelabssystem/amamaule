<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /**
     * Show the mandatory password change form.
     */
    public function show(): View
    {
        if (! Auth::user()->must_change_password) {
            return view('dashboard');
        }

        return view('auth.passwords.change');
    }

    /**
     * Handle the mandatory password change.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->must_change_password) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        Auth::logoutOtherDevices($validated['password']);

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('status', 'Tu contraseña ha sido actualizada correctamente.');
    }
}
