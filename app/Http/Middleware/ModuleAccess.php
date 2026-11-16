<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AccessRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(AccessRoles::allows($request->user(), (string) $request->route()?->getName()), 403, 'Su cuenta no tiene permiso para realizar esta acción.');
        return $next($request);
    }
}