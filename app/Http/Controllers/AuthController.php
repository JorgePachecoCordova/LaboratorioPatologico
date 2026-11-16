<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Muestra la pantalla de inicio de sesión existente.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('inicio');
        }

        return view('auth.login');
    }

    /**
     * Autentica al usuario contra la tabla users.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $authenticated = Auth::attempt([
            'username' => trim($credentials['username']),
            'password' => $credentials['password'],
            'is_active' => true,
        ]);

        if (!$authenticated) {
            return back()
                ->withErrors([
                    'username' => 'Usuario o contraseña incorrectos.',
                ])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('inicio'));
    }

    /**
     * Finaliza la sesión de forma segura.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
