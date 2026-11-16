<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthSession
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Su sesión ha finalizado. Inicie sesión nuevamente.'], 401);
            }
            return redirect()->route('login');
        }

        if (!Auth::user()?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'La cuenta se encuentra deshabilitada.'], 401);
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'La cuenta se encuentra deshabilitada.',
                ]);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        return $response;
    }
}
