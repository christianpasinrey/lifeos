<?php

namespace App\Modules\Admin\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->is_admin) {
            if ($request->expectsJson()) {
                abort(403);
            }
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
