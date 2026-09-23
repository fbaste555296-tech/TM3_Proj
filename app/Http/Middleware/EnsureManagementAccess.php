<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagementAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isManagement()) {
            return redirect()->route('home')->with('error', 'This area is reserved for Triple-M3 staff.');
        }

        return $next($request);
    }
}
