<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStallBookingDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === 'stallbooking.sgcci.in') {
            $url = 'https://registration.sgcci.in'.$request->getRequestUri();

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}
