<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ActivityLogger
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only log write operations
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Skip logging API routes for now
        if ($request->is('api/*')) {
            return $response;
        }

        // Skip if no logged-in user (guest pages)
        if (!Auth::check()) {
            return $response;
        }

        // Identify module name
        $RouteName = $request->route()->getName();
        $ModuleName = explode('.', $RouteName)[1] ?? $RouteName;

        // Save log
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => $request->method(),
            'module'      => $ModuleName,
            'reference_id'=> null,   // can be filled manually for special cases
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'description' => json_encode([
                'url'     => $request->url(),
                'payload' => $request->except(['password', 'password_confirmation']),
            ]),
        ]);

        return $response;
    }
}
