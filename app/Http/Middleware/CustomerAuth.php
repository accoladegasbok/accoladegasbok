<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CustomerAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::get('customer_id')) {
            return redirect()->route('customer.login')->with('error', 'Please log in to continue.');
        }
        return $next($request);
    }
}
