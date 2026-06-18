<?php
// FILE: app/Http/Middleware/AdminAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminAuth
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!Session::get('staff_id')) {
            return redirect()->route('admin.login')
                ->with('error', 'Please log in to access the admin panel.');
        }

        // Role check if roles specified
        if (!empty($roles) && !in_array(Session::get('staff_role'), $roles)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Register in app/Http/Kernel.php — add to $routeMiddleware:
//   'admin.auth' => \App\Http\Middleware\AdminAuth::class,
// ─────────────────────────────────────────────────────────────────────────────
