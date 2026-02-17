<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasModule($module)) {
            abort(403, "No tienes acceso al módulo «{$module}».");
        }

        return $next($request);
    }
}
