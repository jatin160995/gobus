<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;

/**
 * Hides a TaxiGo admin page that is switched off in config/taxigo.php.
 */
class TaxiGoAdminPage
{
    public function handle(Request $request, Closure $next, string $page)
    {
        abort_unless(Modules::adminPage($page), 404);

        return $next($request);
    }
}
