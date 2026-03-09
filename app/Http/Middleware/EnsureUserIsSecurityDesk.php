<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSecurityDesk
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isSecurityDesk()) {
            abort(403, 'Unauthorized access. Security desk privileges required.');
        }

        return $next($request);
    }
}
