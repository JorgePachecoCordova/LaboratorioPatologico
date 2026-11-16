<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (!$user->hasRole('administrator') && $user->role !== 'administrator')) {
            abort(403, 'No tiene permisos para acceder a configuración.');
        }

        return $next($request);
    }
}
