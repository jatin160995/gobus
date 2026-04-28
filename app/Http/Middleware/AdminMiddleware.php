<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return redirect('/login');
        }

        return $next($request);
    }
}
