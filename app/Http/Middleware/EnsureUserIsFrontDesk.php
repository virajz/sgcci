<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFrontDesk
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isFrontDesk()) {
            abort(403, 'Unauthorized access. Front desk privileges required.');
        }

        return $next($request);
    }
}
